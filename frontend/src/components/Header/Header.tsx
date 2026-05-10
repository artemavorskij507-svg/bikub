'use client'

import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import Link from 'next/link'
import { useLocale, useTranslations } from 'next-intl'
import clsx from 'clsx'
import styles from './Header.module.css'
import { FastOrderForm } from './FastOrderForm'

type ServiceNavItem = {
  id: string
  icon: string
  title: string
  description: string
  href: string
}

type PrimaryLink = {
  id: string
  href: string
}

const MOBILE_BREAKPOINT = 1024

export function Header() {
  const t = useTranslations('Header')
  const locale = useLocale()

  const [mobileOpen, setMobileOpen] = useState(false)
  const [servicesOpen, setServicesOpen] = useState(false)
  const [isScrolled, setIsScrolled] = useState(false)
  const [hideOnScroll, setHideOnScroll] = useState(false)
  const [cartCount] = useState(0)

  const lastScrollY = useRef(0)
  const ticking = useRef(false)

  const services = useMemo<ServiceNavItem[]>(() => (
    [
      {
        id: 'delivery',
        icon: '🚚',
        title: t('services.delivery.title'),
        description: t('services.delivery.description'),
        href: `/${locale}/catalog?category=delivery`
      },
      {
        id: 'bulky',
        icon: '📦',
        title: t('services.bulky.title'),
        description: t('services.bulky.description'),
        href: `/${locale}/catalog?category=bulky`
      },
      {
        id: 'food',
        icon: '🍲',
        title: t('services.food.title'),
        description: t('services.food.description'),
        href: `/${locale}/catalog?category=food`
      },
      {
        id: 'handyman',
        icon: '🛠️',
        title: t('services.handyman.title'),
        description: t('services.handyman.description'),
        href: `/${locale}/catalog?category=handyman`
      },
      {
        id: 'eco',
        icon: '🌿',
        title: t('services.eco.title'),
        description: t('services.eco.description'),
        href: `/${locale}/catalog?category=eco`
      },
      {
        id: 'roadside',
        icon: '🚗',
        title: t('services.roadside.title'),
        description: t('services.roadside.description'),
        href: `/${locale}/catalog?category=roadside`
      },
      {
        id: 'personal',
        icon: '🤝',
        title: t('services.personal.title'),
        description: t('services.personal.description'),
        href: `/${locale}/catalog?category=personal`
      }
    ]
  ), [locale, t])

  const primaryLinks = useMemo<PrimaryLink[]>(() => (
    [
      { id: 'howItWorks', href: '#how-it-works' },
      { id: 'about', href: '#about' },
      { id: 'contact', href: '#contact' },
      { id: 'partnership', href: '#partnership' }
    ]
  ), [])

  const toggleMobileMenu = useCallback(() => {
    setMobileOpen((prev) => !prev)
    setServicesOpen(false)
  }, [])

  const handleScroll = useCallback(() => {
    const currentScroll = window.scrollY
    setIsScrolled(currentScroll > 40)

    if (window.innerWidth <= MOBILE_BREAKPOINT) {
      setHideOnScroll(false)
      return
    }

    if (currentScroll <= 0) {
      setHideOnScroll(false)
      lastScrollY.current = 0
      return
    }

    const delta = currentScroll - lastScrollY.current
    if (Math.abs(delta) > 6) {
      setHideOnScroll(delta > 0)
      lastScrollY.current = currentScroll
    }
  }, [])

  useEffect(() => {
    const onScroll = () => {
      if (!ticking.current) {
        window.requestAnimationFrame(() => {
          handleScroll()
          ticking.current = false
        })
        ticking.current = true
      }
    }

    handleScroll()
    window.addEventListener('scroll', onScroll, { passive: true })

    return () => {
      window.removeEventListener('scroll', onScroll)
    }
  }, [handleScroll])

  useEffect(() => {
    if (window.innerWidth > MOBILE_BREAKPOINT) {
      setMobileOpen(false)
    }
  }, [])

  return (
    <header
      className={clsx(
        styles.header,
        !isScrolled && styles.headerTransparent,
        isScrolled && styles.headerShadow,
        hideOnScroll && styles.headerHidden
      )}
      aria-label={t('aria.header')}
    >
      <div className={styles.inner}>
        <Link href={`/${locale}`} className={styles.logoWrap} aria-label={t('aria.home')}>
          <span className={styles.logoMark}>G</span>
          <span className={styles.logoText}>
            <span className={styles.logoTitle}>GLF BiKube</span>
            <span className={styles.logoSubtitle}>{t('tagline')}</span>
          </span>
        </Link>

        <nav className={styles.nav} aria-label={t('aria.primaryNav')}>
          <div
            className={styles.dropdown}
            onMouseEnter={() => setServicesOpen(true)}
            onMouseLeave={() => setServicesOpen(false)}
          >
            <button
              className={styles.dropdownButton}
              onClick={() => setServicesOpen((prev) => !prev)}
              aria-haspopup="true"
              aria-expanded={servicesOpen}
            >
              {t('allServices')} <span aria-hidden="true">▾</span>
            </button>
            {servicesOpen && (
              <div className={styles.dropdownMenu} role="menu">
                {services.map((service) => (
                  <Link
                    key={service.id}
                    href={service.href}
                    className={styles.dropdownItem}
                    role="menuitem"
                    onClick={() => setServicesOpen(false)}
                  >
                    <span className={styles.dropdownIcon} aria-hidden="true">{service.icon}</span>
                    <span className={styles.dropdownText}>
                      <span className={styles.dropdownTitle}>{service.title}</span>
                      <span className={styles.dropdownDescription}>{service.description}</span>
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </div>

          {primaryLinks.map((item) => (
            <a key={item.id} href={item.href} className={styles.navLink}>
              {t(`nav.${item.id}`)}
            </a>
          ))}
        </nav>

        <div className={styles.fastOrderWrapper}>
          <FastOrderForm />
        </div>

        <div className={styles.actions}>
          <div className={styles.authButtons}>
            <Link href={`/${locale}/login`} className={styles.loginButton}>
              {t('login')}
            </Link>
            <Link href={`/${locale}/register`} className={styles.registerButton}>
              {t('register')}
            </Link>
          </div>

          <Link href={`/${locale}/cart`} className={styles.cartButton} aria-label={t('aria.openCart')}>
            <span aria-hidden="true">🛒</span>
            {cartCount > 0 && <span className={styles.cartCount}>{cartCount}</span>}
          </Link>

          <button className={styles.mobileToggle} onClick={toggleMobileMenu} aria-expanded={mobileOpen}>
            <span aria-hidden="true">☰</span>
            <span>{mobileOpen ? t('close') : t('menu')}</span>
          </button>
        </div>
      </div>

      <div className={clsx(styles.mobileMenu, mobileOpen && 'open')}>
        <div className={styles.mobileServices}>
          <span className={styles.mobileLabel}>{t('allServices')}</span>
          {services.map((service) => (
            <Link key={service.id} href={service.href} onClick={() => setMobileOpen(false)}>
              {service.icon} {service.title}
            </Link>
          ))}
        </div>
        <div className={styles.mobileServices}>
          {primaryLinks.map((link) => (
            <a key={link.id} href={link.href} onClick={() => setMobileOpen(false)}>
              {t(`nav.${link.id}`)}
            </a>
          ))}
        </div>
        <div className={styles.mobileServices}>
          <Link href={`/${locale}/login`} onClick={() => setMobileOpen(false)}>
            {t('login')}
          </Link>
          <Link href={`/${locale}/register`} onClick={() => setMobileOpen(false)}>
            {t('register')}
          </Link>
        </div>
        <FastOrderForm onSubmit={() => setMobileOpen(false)} />
      </div>
    </header>
  )
}
