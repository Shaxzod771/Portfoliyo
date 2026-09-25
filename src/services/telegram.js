// Sends contact-form submissions to a Telegram chat through the Bot API.
// Credentials come from environment variables (see .env.example) and are never committed.
const BOT_TOKEN = import.meta.env.VITE_TG_BOT_TOKEN;
const CHAT_ID = import.meta.env.VITE_TG_CHAT_ID;

const escapeHtml = (value) =>
  String(value).replace(/[&<>"]/g, (ch) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[ch]));

export const isTelegramConfigured = Boolean(BOT_TOKEN && CHAT_ID);

export async function sendContactMessage({ name, email, subject, message, lang }) {
  if (!isTelegramConfigured) {
    throw new Error("Telegram is not configured (VITE_TG_BOT_TOKEN / VITE_TG_CHAT_ID missing)");
  }

  const text = [
    "📩 <b>Portfoliodan yangi xabar</b>",
    "",
    `<b>Ism:</b> ${escapeHtml(name)}`,
    `<b>Email:</b> ${escapeHtml(email)}`,
    subject ? `<b>Mavzu:</b> ${escapeHtml(subject)}` : null,
    "",
    escapeHtml(message),
    "",
    `<i>Til: ${escapeHtml(lang)} · ${new Date().toLocaleString("uz-UZ")}</i>`,
  ]
    .filter((line) => line !== null)
    .join("\n");

  const response = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ chat_id: CHAT_ID, text, parse_mode: "HTML" }),
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok || !data.ok) {
    throw new Error(data.description || `Telegram API error ${response.status}`);
  }
}
