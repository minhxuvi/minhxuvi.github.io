import { removeLinks } from 'nextra/remove-links'

/**
 * Table of contents for small screens, where Nextra hides its floating TOC
 * (`.nextra-toc` is hidden below 1280px). It is rendered above the article
 * body inside `<main>`, collapsed by default, and hidden again from 1280px up
 * so it never shows next to the built-in TOC.
 *
 * Shown only for long articles, where jumping between headings actually
 * matters.
 */
const MIN_HEADINGS = 8
const MAX_DEPTH = 4

export function MobileToc({ toc }) {
  const items = (toc || []).filter(
    (item) => item.depth >= 2 && item.depth <= MAX_DEPTH
  )
  if (items.length < MIN_HEADINGS) {
    return null
  }

  return (
    <details className="site-mobile-toc" data-pagefind-ignore="all">
      <summary>Mục lục</summary>
      <nav aria-label="Mục lục trang">
        <ol>
          {items.map((item) => (
            <li key={item.id} data-depth={item.depth}>
              <a href={`#${item.id}`}>{removeLinks(item.value)}</a>
            </li>
          ))}
        </ol>
      </nav>
    </details>
  )
}
