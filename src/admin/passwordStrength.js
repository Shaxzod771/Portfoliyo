// Mirrors backend/src/Services/PasswordPolicy.php so problems show up while typing.
// The server still checks everything; this is only a hint.
export const MIN_LENGTH = 10;

export function passwordProblem(password, username = "") {
  if (password.length < MIN_LENGTH) return `Kamida ${MIN_LENGTH} ta belgi`;
  if (!/\p{L}/u.test(password) || !/\d/.test(password)) return "Kamida bitta harf va bitta raqam bo‘lsin";
  if (new Set(password).size < 5) return "Juda oddiy — turli belgilardan foydalaning";
  if (username && password.toLowerCase().includes(username.toLowerCase())) return "Parolda login bo‘lmasin";
  return null;
}

/** 0–4, for the strength bar */
export function passwordScore(password) {
  if (!password) return 0;
  let score = 0;
  if (password.length >= MIN_LENGTH) score++;
  if (password.length >= 14) score++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  if (/\d/.test(password) && /[^\p{L}\d]/u.test(password)) score++;
  return passwordProblem(password) ? Math.min(score, 1) : Math.max(score, 2);
}

export const SCORE_LABELS = ["", "Zaif", "O‘rtacha", "Yaxshi", "Kuchli"];
