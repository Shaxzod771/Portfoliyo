// Field names the backend expects for a project (see ProjectController::validated)
export const TEXT_FIELDS = ["title_uz", "title_en", "title_ru", "desc_uz", "desc_en", "desc_ru", "github_url", "live_url"];

/** Builds the multipart body for create/update; `image` is a File, or omitted to keep the current one */
export function projectFormData(values, { image = null, removeImage = false } = {}) {
  const body = new FormData();
  for (const field of TEXT_FIELDS) body.append(field, values[field] ?? "");
  body.append("layout", values.layout || "regular");
  body.append("image_fit", values.image_fit || "cover");
  body.append("preview_mode", values.preview_mode || "image");
  body.append("is_published", values.is_published ? "1" : "0");
  if (image) body.append("image", image);
  else if (removeImage) body.append("remove_image", "1");
  return body;
}
