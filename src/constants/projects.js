import educationImg from "@/assets/project/Education.webp";
import newsImg from "@/assets/project/news.avif";
import crmImg from "@/assets/project/CRM.webp";
import marketImg from "@/assets/project/Market.webp";
import adminImg from "@/assets/project/admin.webp";

// `key` links a project to its title/description in translations.js (projects.items).
// Leave `github` or `live` as null when there is no link — the button is then hidden.
// `fit: "contain"` shows logo-style images whole instead of cropping them.
// `preview: "live"` shows the running site (the `live` link) in the card instead of the image.
export const PROJECTS = [
  {
    key: "education",
    image: educationImg,
    github: "https://github.com/RizaSoft-Group/riza-edu",
    live: null,
    type: "featured",
  },
  {
    key: "news",
    image: newsImg,
    github: "https://github.com/Shaxzod-hp/Shaxzod",
    live: null,
    type: "regular",
  },
  {
    key: "crm",
    image: crmImg,
    fit: "contain",
    github: "https://github.com/Shaxzod-hp/Iso-Uz",
    live: "https://shaxzod-hp.github.io/Iso-Uz/#/access",
    preview: "live",
    type: "regular",
  },
  {
    key: "market",
    image: marketImg,
    github: "https://github.com/Shaxzod-hp/Shaxzod",
    live: null,
    type: "wide",
  },
  {
    key: "admin",
    image: adminImg,
    fit: "contain",
    github: null,
    live: null,
    type: "regular",
  },
];
