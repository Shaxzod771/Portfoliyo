// Address of the PHP backend (backend/public). Empty when the site runs without it,
// e.g. on GitHub Pages — the site then falls back to built-in data and FormSubmit.
export const API_URL = (import.meta.env.VITE_API_URL || "").replace(/\/+$/, "");

export const hasApi = API_URL !== "";

export class ApiError extends Error {
  constructor(message, status, errors = null) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

/** fetch() wrapper for the backend: JSON in and out, errors thrown as ApiError */
export async function apiRequest(path, { method = "GET", body, token, signal } = {}) {
  const headers = {};
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined && !(body instanceof FormData)) headers["Content-Type"] = "application/json";

  let response;
  try {
    response = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      body: body === undefined || body instanceof FormData ? body : JSON.stringify(body),
      signal,
    });
  } catch (err) {
    if (err.name === "AbortError") throw err;
    throw new ApiError("Serverga ulanib bo‘lmadi. Backend ishlayaptimi?", 0);
  }

  if (response.status === 204) return null;
  const data = await response.json().catch(() => null);
  if (!response.ok) {
    throw new ApiError(data?.message || `Server xatosi (${response.status})`, response.status, data?.errors || null);
  }
  return data;
}
