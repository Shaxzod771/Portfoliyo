<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Services\ImageUploader;
use App\Validator;

final class ProjectController
{
    private const LANGS = ['uz', 'en', 'ru'];
    private const LAYOUTS = ['featured', 'regular', 'wide'];
    private const FITS = ['cover', 'contain'];

    /** Public: published projects for the portfolio site */
    public static function publicIndex(Request $request): array
    {
        $rows = Database::select('SELECT * FROM projects WHERE is_published = 1 ORDER BY sort_order, id');
        return ['data' => array_map(self::present(...), $rows)];
    }

    public static function index(Request $request): array
    {
        $rows = Database::select('SELECT * FROM projects ORDER BY sort_order, id');
        return ['data' => array_map(self::present(...), $rows)];
    }

    public static function show(Request $request): array
    {
        return ['data' => self::present(self::find($request->params['id']))];
    }

    /** multipart/form-data, because it may carry an image */
    public static function store(Request $request): Response
    {
        $data = self::validated($request);

        $image = $request->file('image');
        $data['image'] = $image ? ImageUploader::store($image) : null;
        $data['sort_order'] = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM projects');
        $data['created_at'] = $data['updated_at'] = Database::now();

        $id = Database::insert('projects', $data);
        return Response::json(['data' => self::present(self::find($id))], 201);
    }

    /** POST with multipart/form-data (PHP does not parse file uploads on PUT) */
    public static function update(Request $request): array
    {
        $project = self::find($request->params['id']);
        $data = self::validated($request);

        $oldImage = $project['image'];
        $image = $request->file('image');
        if ($image) {
            $data['image'] = ImageUploader::store($image);
        } elseif ($request->bool('remove_image')) {
            $data['image'] = null;
        }
        $data['updated_at'] = Database::now();

        Database::update('projects', (int) $project['id'], $data);

        // Delete the old file only after the database points to the new one
        if (array_key_exists('image', $data) && $data['image'] !== $oldImage) {
            ImageUploader::delete($oldImage);
        }
        return ['data' => self::present(self::find((int) $project['id']))];
    }

    public static function destroy(Request $request): Response
    {
        $project = self::find($request->params['id']);
        Database::execute('DELETE FROM projects WHERE id = ?', [$project['id']]);
        ImageUploader::delete($project['image']);
        return Response::noContent();
    }

    /** Body: { "ids": [3, 1, 2] } — the new display order */
    public static function reorder(Request $request): array
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || $ids === [] || array_filter($ids, fn ($id) => !is_int($id) || $id < 1)) {
            throw HttpException::validation(['ids' => 'Loyiha ID lari roʻyxati kerak']);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE projects SET sort_order = ? WHERE id = ?');
            foreach (array_values(array_unique($ids)) as $position => $id) {
                $stmt->execute([$position + 1, $id]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::index($request);
    }

    private static function find(int $id): array
    {
        return Database::first('SELECT * FROM projects WHERE id = ?', [$id]) ?? throw HttpException::notFound('Loyiha topilmadi');
    }

    private static function validated(Request $request): array
    {
        $data = [];
        $v = new Validator();

        foreach (self::LANGS as $lang) {
            $title = $request->string("title_$lang");
            $desc = $request->string("desc_$lang");
            $v->max("title_$lang", $title, 150)->max("desc_$lang", $desc, 1000);
            $data["title_$lang"] = $title;
            $data["desc_$lang"] = $desc === '' ? null : $desc;
        }
        $v->required('title_uz', $data['title_uz'], 'Oʻzbekcha nom majburiy');

        foreach (['github_url', 'live_url'] as $field) {
            $url = $request->string($field);
            $v->url($field, $url)->max($field, $url, 255);
            $data[$field] = $url === '' ? null : $url;
        }

        $data['layout'] = $request->string('layout') ?: 'regular';
        $data['image_fit'] = $request->string('image_fit') ?: 'cover';
        $v->in('layout', $data['layout'], self::LAYOUTS)->in('image_fit', $data['image_fit'], self::FITS);

        $data['is_published'] = $request->bool('is_published', true) ? 1 : 0;

        $v->validate();
        return $data;
    }

    /** Shapes a database row for the API; titles fall back to Uzbek when a translation is empty */
    private static function present(array $row): array
    {
        $title = $description = [];
        foreach (self::LANGS as $lang) {
            $title[$lang] = $row["title_$lang"] !== '' ? $row["title_$lang"] : $row['title_uz'];
            $description[$lang] = $row["desc_$lang"] ?? $row['desc_uz'] ?? '';
        }

        return [
            'id'           => (int) $row['id'],
            'title'        => $title,
            'description'  => $description,
            // Raw per-language values, so the admin form shows what is actually stored
            'fields'       => [
                'title_uz' => $row['title_uz'], 'title_en' => $row['title_en'], 'title_ru' => $row['title_ru'],
                'desc_uz'  => $row['desc_uz'] ?? '', 'desc_en' => $row['desc_en'] ?? '', 'desc_ru' => $row['desc_ru'] ?? '',
            ],
            'image_url'    => $row['image'] ? self::publicUrl($row['image']) : null,
            'image_fit'    => $row['image_fit'],
            'github_url'   => $row['github_url'],
            'live_url'     => $row['live_url'],
            'layout'       => $row['layout'],
            'sort_order'   => (int) $row['sort_order'],
            'is_published' => (bool) $row['is_published'],
            'created_at'   => $row['created_at'],
            'updated_at'   => $row['updated_at'],
        ];
    }

    private static function publicUrl(string $relative): string
    {
        $base = rtrim((string) Config::get('app_url', ''), '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
            $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
        }
        return $base . '/' . $relative;
    }
}
