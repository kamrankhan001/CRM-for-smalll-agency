import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind and conditional classes
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Check if a URL is active (supports subpaths like /users/create, pagination ?page=2, etc.)
 */
export function urlIsActive(
  urlToCheck: NonNullable<InertiaLinkProps['href']>,
  currentUrl: string
) {
  const base = toUrl(urlToCheck);
  if (!base || !currentUrl) return false;

  // Normalize both URLs (remove trailing slashes + query strings)
  const normalize = (url: string) =>
    url.replace(/\/+$/, '').replace(/\?.*$/, '');

  const normalizedBase = normalize(base);
  const normalizedCurrent = normalize(currentUrl);

  // Match if current starts with base (handles subroutes and pagination)
  return (
    normalizedCurrent === normalizedBase ||
    normalizedCurrent.startsWith(`${normalizedBase}/`)
  );
}

/**
 * Extract URL string from Inertia Link href prop
 */
export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
  return typeof href === 'string' ? href : href?.url;
}
