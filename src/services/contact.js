import { CONTACT } from "../constants/site";
import { apiRequest, hasApi } from "./api";

// Contact-form messages are delivered to CONTACT.email:
// - with the backend: saved in the database (visible in the admin panel) and emailed over SMTP;
// - without it (GitHub Pages): sent through FormSubmit, which forwards them to the same inbox.
//   FormSubmit emails an activation link on the very first submission — open it once.
const FORMSUBMIT_URL = `https://formsubmit.co/ajax/${CONTACT.email}`;

export async function sendContactMessage({ name, email, subject, message, lang }) {
  if (hasApi) {
    await apiRequest("/api/messages", { method: "POST", body: { name, email, subject, message, lang } });
    return;
  }

  const response = await fetch(FORMSUBMIT_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({
      name,
      email,
      message,
      subject: subject || "—",
      lang,
      _subject: `Portfolio: ${subject || "yangi xabar"} — ${name}`,
      _replyto: email,
      _template: "table",
      _captcha: "false",
    }),
  });

  const data = await response.json().catch(() => ({}));
  // FormSubmit answers with success as the string "true"
  if (!response.ok || String(data.success) !== "true") {
    throw new Error(data.message || `FormSubmit error ${response.status}`);
  }
}
