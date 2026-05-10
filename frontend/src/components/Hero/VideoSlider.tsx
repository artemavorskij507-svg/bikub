'use client'

import { useEffect, useMemo, useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import clsx from 'clsx'
import { useLocale, useTranslations } from 'next-intl'
import { useSliderControls } from './useSliderControls'
import styles from './VideoSlider.module.css'
import { getVideoSlides, VIDEO_SLIDES_FALLBACK } from '@/services/cmsService'

export type VideoSlide = {
  id: string
  title: string
  subtitle: string
  eyebrow?: string
  ctaLabel: string
  ctaHref: string
  secondaryCtaLabel?: string
  secondaryCtaHref?: string
  videoUrl?: string
  posterUrl?: string
  accentColor?: string
}

export function VideoSlider() {
  const t = useTranslations('Hero')
  const locale = useLocale()
  const [slides, setSlides] = useState<VideoSlide[]>(VIDEO_SLIDES_FALLBACK)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true
    getVideoSlides()
      .then((data) => {
        if (mounted && data.length) {
          setSlides(data)
        }
      })
      .catch(() => {
        // fallback already set
      })
      .finally(() => {
        if (mounted) setLoading(false)
      })
    return () => {
      mounted = false
    }
  }, [])

  const controls = useSliderControls({ slideCount: slides.length, interval: 5000, autoplay: true })

  const palette = useMemo(() => (
    slides.map((slide) => slide.accentColor ?? '#22d3ee')
  ), [slides])

  if (!slides.length) {
    return null
  }

  return (
    <section
      className={styles.slider}
      onMouseEnter={controls.pause}
      onMouseLeave={controls.resume}
      onKeyDown={controls.handleKeyDown}
      tabIndex={0}
      aria-roledescription="carousel"
      aria-label={t('aria.slider')}
    >
      <div className={styles.slides}>
        {slides.map((slide, index) => {
          const isActive = index === controls.currentIndex
          const accent = palette[index]

          return (
            <article
              key={slide.id}
              className={clsx(styles.slide, isActive && styles.slideActive)}
              aria-hidden={!isActive}
            >
              <div className={styles.videoWrap}>
                {slide.videoUrl ? (
                  <video
                    key={slide.videoUrl}
                    src={slide.videoUrl}
                    poster={slide.posterUrl}
                    muted
                    loop
                    playsInline
                    autoPlay={isActive}
                  />
                ) : slide.posterUrl ? (
                  <Image
                    src={slide.posterUrl}
                    alt=""
                    fill
                    priority
                    sizes="100vw"
                    style={{ objectFit: 'cover' }}
                  />
                ) : null}
                <div className={styles.overlay} />
              </div>

              <div className={styles.content}>
                {slide.eyebrow && (
                  <span className={styles.eyebrow} style={{ color: accent }}>
                    {slide.eyebrow}
                  </span>
                )}
                <h1 className={styles.title}>{slide.title}</h1>
                <p className={styles.subtitle}>{slide.subtitle}</p>
                <div className={styles.ctaGroup}>
                  <Link className={styles.ctaPrimary} href={`/${locale}${slide.ctaHref}`} style={{ color: '#0f172a' }}>
                    {slide.ctaLabel}
                    <span aria-hidden="true">↗</span>
                  </Link>
                  {slide.secondaryCtaHref && slide.secondaryCtaLabel && (
                    <Link className={styles.ctaSecondary} href={`/${locale}${slide.secondaryCtaHref}`}>
                      {slide.secondaryCtaLabel}
                    </Link>
                  )}
                </div>
              </div>
            </article>
          )
        })}
      </div>

      <div className={styles.controls} aria-hidden={loading}>
        <button className={styles.arrowButton} onClick={controls.previousSlide} aria-label={t('aria.prevSlide')}>
          ‹
        </button>
        <div className={styles.dots} role="tablist">
          {slides.map((slide, index) => (
            <button
              key={slide.id}
              className={clsx(styles.dot, index === controls.currentIndex && styles.dotActive)}
              onClick={() => controls.goToSlide(index)}
              aria-label={t('aria.goToSlide', { number: index + 1 })}
              aria-selected={index === controls.currentIndex}
              role="tab"
            />
          ))}
        </div>
        <button className={styles.arrowButton} onClick={controls.nextSlide} aria-label={t('aria.nextSlide')}>
          ›
        </button>
      </div>
    </section>
  )
}
