'use client'

import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { CSSProperties } from 'react'
import { useTranslations } from 'next-intl'
import styles from './Services3DGrid.module.css'
import { ServiceCard, ServiceCardData } from './ServiceCard'
import { useMouseParallax } from './useMouseParallax'
import { getServices, SERVICES_FALLBACK } from '@/services/cmsService'

const DEPTH_LEVELS = [12, 18, 25]

export function Services3DGrid() {
  const t = useTranslations('ServicesGrid')
  const [services, setServices] = useState<ServiceCardData[]>(SERVICES_FALLBACK)
  const [visible, setVisible] = useState<boolean[]>(SERVICES_FALLBACK.map(() => false))
  const cardsRef = useRef<(HTMLDivElement | null)[]>([])

  useEffect(() => {
    let mounted = true
    getServices()
      .then((data) => {
        if (mounted && data.length) {
          setServices(data)
          setVisible(data.map(() => false))
        }
      })
      .catch(() => {
        /* fallback already set */
      })

    return () => {
      mounted = false
    }
  }, [])

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return
          const index = Number(entry.target.getAttribute('data-index'))
          setVisible((prev) => {
            if (prev[index]) return prev
            const next = [...prev]
            next[index] = true
            return next
          })
          observer.unobserve(entry.target)
        })
      },
      {
        threshold: 0.35
      }
    )

    cardsRef.current.forEach((node) => {
      if (node) observer.observe(node)
    })

    return () => observer.disconnect()
  }, [services])

  const { ref: gridRef, state: parallaxState } = useMouseParallax({ intensity: 12, friction: 0.1 })

  const cardTransforms = useMemo(() => (
    services.map((_, index) => {
      const depth = DEPTH_LEVELS[index % DEPTH_LEVELS.length]
      const rotateX = parallaxState.rotateX * 0.35
      const rotateY = parallaxState.rotateY * 0.35
      const translateZ = depth + parallaxState.translateZ * 0.4

      return {
        transform: `perspective(1200px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(${translateZ}px)`,
        opacity: visible[index] ? 1 : 0,
        transition: visible[index]
          ? 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.22, 0.61, 0.36, 1)'
          : 'opacity 0.3s ease'
      } as CSSProperties
    })
  ), [services, parallaxState, visible])

  const setCardRef = useCallback((node: HTMLDivElement | null, index: number) => {
    cardsRef.current[index] = node
  }, [])

  return (
    <section className={styles.gridSection}>
      <div className={styles.backgroundGlow} aria-hidden="true" />
      <div className={styles.sectionInner}>
        <header className={styles.header}>
          <span className={styles.eyebrow}>{t('eyebrow')}</span>
          <h2 className={styles.title}>{t('title')}</h2>
          <p className={styles.subtitle}>{t('subtitle')}</p>
        </header>

        <div ref={gridRef} className={styles.cardsGrid}>
          {services.map((service, index) => (
            <div
              key={service.id}
              ref={(node) => setCardRef(node, index)}
              data-index={index}
              style={cardTransforms[index]}
            >
              <ServiceCard service={service} />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
