'use client'

import { useEffect, useRef } from 'react'
import { MenuIcon } from 'nextra/icons'
import { setMenu, useMenu } from 'nextra-theme-docs'

const PANEL_ID = 'site-mobile-nav'
const PANEL_SELECTOR = '.nextra-mobile-nav'

/**
 * Mobile menu toggle for the docs theme.
 *
 * Nextra renders an icon-only hamburger with an English `aria-label`, and its
 * off-canvas panel stays in the tab order while closed. This button replaces
 * the hamburger (hidden in `app/globals.css`) with a labelled Vietnamese
 * toggle and adds the missing focus handling: the panel is `inert` while
 * closed, focus moves into it when opened, and Escape closes it and returns
 * focus to the button.
 */
export function SiteMenuButton() {
  const open = useMenu()
  const buttonRef = useRef(null)

  // Tag the theme's panel so the button can reference it (`aria-controls`).
  useEffect(() => {
    const panel = document.querySelector(PANEL_SELECTOR)
    if (!panel) {
      return
    }
    panel.id = PANEL_ID
    panel.setAttribute('role', 'dialog')
    panel.setAttribute('aria-modal', 'true')
    panel.setAttribute('aria-label', 'Menu chính')
  }, [])

  // Keep the panel out of the tab order while closed; focus it when opened.
  useEffect(() => {
    const panel = document.getElementById(PANEL_ID)
    if (!panel) {
      return
    }
    if (open) {
      panel.removeAttribute('inert')
      const first = panel.querySelector('input, a[href], button')
      first?.focus({ preventScroll: true })
    } else {
      panel.setAttribute('inert', '')
    }
  }, [open])

  // Escape closes the menu and restores focus to the toggle.
  useEffect(() => {
    if (!open) {
      return
    }
    const onKeyDown = (event) => {
      if (event.key !== 'Escape' || event.defaultPrevented) {
        return
      }
      setMenu(false)
      buttonRef.current?.focus({ preventScroll: true })
    }
    document.addEventListener('keydown', onKeyDown)
    return () => document.removeEventListener('keydown', onKeyDown)
  }, [open])

  return (
    <button
      ref={buttonRef}
      type="button"
      className="site-menu-button"
      aria-expanded={open}
      aria-controls={PANEL_ID}
      onClick={() => setMenu((value) => !value)}
    >
      <MenuIcon height="18" aria-hidden="true" />
      <span>Menu</span>
    </button>
  )
}
