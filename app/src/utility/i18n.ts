import { get } from "lodash-es";

export default function (
  path: string,
  replacements: { [key: string]: string } | null = null
): string {
  if (!collectme.t) {
    return path;
  }

  let translated: string = get(collectme.t, path, path);

  if (replacements) {
    for (const key in replacements) {
      translated = translated.replace(`{${key}}`, replacements[key]);
    }
  }

  return translated;
}
