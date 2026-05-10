'use client'

import Link from 'next/link'
import clsx from 'clsx'
import styles from './Services3DGrid.module.css'

export interface ServiceCardData {
  id: string
  title: string
  description: string
  icon: string
  accent: string
  tags: string[]
  ctaLabel: string
  href: string
}

interface ServiceCardProps {
  service: ServiceCardData
  style?: React.CSSProperties
}

export function ServiceCard({ service, style }: ServiceCardProps) {
  return (
    <div className={styles.cardWrapper} style={style}>
      <article className={styles.card}>
        <div
          className={styles.iconWrap}
          style={{
            background: `linear-gradient(135deg, ${service.accent}33, ${service.accent}11)`
          }}
        >
          <span aria-hidden="true">{service.icon}</span>
        </div>

        <h3 className={styles.cardTitle}>{service.title}</h3>
        <p className={styles.cardDescription}>{service.description}</p>

        <div className={styles.tags}>
          {service.tags.map((tag) => (
            <span key={tag} className={styles.tag}>
              {tag}
            </span>
          ))}
        </div>

        <Link href={service.href} className={clsx(styles.ctaButton)}>
          <span>{service.ctaLabel}</span>
          <span aria-hidden="true">→</span>
        </Link>
      </article>
    </div>
  )
}
