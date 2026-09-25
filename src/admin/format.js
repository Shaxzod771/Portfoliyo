// The API sends "YYYY-MM-DD HH:MM:SS" in the server's time zone (Asia/Tashkent)
export function formatDate(value, withTime = true) {
  if (!value) return "";
  const [date, time = ""] = value.split(" ");
  const [y, m, d] = date.split("-");
  return withTime ? `${d}.${m}.${y} ${time.slice(0, 5)}` : `${d}.${m}.${y}`;
}

export const LANG_LABELS = { uz: "O‘zbekcha", en: "English", ru: "Русский" };
