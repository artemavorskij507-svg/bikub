'use client'

import { useState, useEffect } from 'react'
import { useLocale } from 'next-intl'
import Link from 'next/link'
import { motion } from 'framer-motion'

interface Service {
  id: string
  name: string
  description: string
  category: string
  price: number
  currency: string
}

interface Category {
  id: string
  color: string
  icon: string
}

const categories: Category[] = [
  { id: 'care', color: '#10b981', icon: '🚴' },
  { id: 'eco', color: '#22c55e', icon: '🌱' },
  { id: 'tow', color: '#ef4444', icon: '🚛' },
  { id: 'handyman', color: '#f59e0b', icon: '🔧' },
  { id: 'errands', color: '#8b5cf6', icon: '📦' },
  { id: 'moving', color: '#3b82f6', icon: '🏠' },
]

function Background({ active }: { active: string }) {
  const c = categories.find(x => x.id === active) || categories[0]
  return (
    <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(135deg,' + c.color + '15,' + c.color + '05)', transition: 'all 0.7s' }}>
      <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', width: 800, height: 800, borderRadius: '50%', filter: 'blur(120px)', background: c.color, opacity: 0.3 }} />
    </div>
  )
}

interface CardProps {
  service: Service
  index: number
  onHover: (category: Category) => void
  onLeave: () => void
}

function Card({ service, index, onHover, onLeave }: CardProps) {
  const c = categories.find(x => x.id === service.category) || categories[0]
  const locale = useLocale()
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: index * 0.1 }}
      onMouseEnter={() => onHover(c)}
      onMouseLeave={onLeave}
      style={{ position: 'relative', background: 'rgba(255,255,255,0.8)', backdropFilter: 'blur(20px)', borderRadius: 16, border: '1px solid rgba(255,255,255,0.2)', overflow: 'hidden', transition: 'all 0.3s' }}
    >
      <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 4, background: c.color }} />
      <div style={{ padding: 24 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 12 }}>
          <span style={{ fontSize: 24 }}>{c.icon}</span>
          <span style={{ padding: '4px 12px', borderRadius: 20, fontSize: 12, background: c.color + '20', color: c.color }}>{service.category}</span>
        </div>
        <h3 style={{ fontSize: 20, fontWeight: 700, marginBottom: 8 }}>{service.name}</h3>
        <p style={{ color: '#666', fontSize: 14, marginBottom: 16 }}>{service.description}</p>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: '1px solid #eee', paddingTop: 16 }}>
          <span style={{ fontSize: 24, fontWeight: 700, color: c.color }}>{service.price} {service.currency}</span>
          <Link href={'/' + locale + '/order/' + service.id} style={{ padding: '8px 16px', borderRadius: 8, background: c.color, color: 'white', textDecoration: 'none' }}>Заказать</Link>
        </div>
      </div>
    </motion.div>
  )
}

export function ServiceGrid() {
  const locale = useLocale()
  const [activeCategory, setActiveCategory] = useState('care')
  const [services, setServices] = useState<Service[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch(process.env.NEXT_PUBLIC_API_URL + '/public/catalog?locale=' + locale)
      .then(res => res.json())
      .then(data => setServices(data.services || []))
      .catch(() => {
        setServices([
          { id: '1', name: 'Bike Maintenance', description: 'Complete bike tune-up', category: 'care', price: 299, currency: 'NOK' },
          { id: '2', name: 'Eco Cleaning', description: 'Eco-friendly cleaning', category: 'eco', price: 199, currency: 'NOK' },
          { id: '3', name: 'Tow Service', description: 'Emergency towing 24/7', category: 'tow', price: 399, currency: 'NOK' },
          { id: '4', name: 'Handyman', description: 'Home repairs', category: 'handyman', price: 499, currency: 'NOK' },
          { id: '5', name: 'Errands', description: 'Quick deliveries', category: 'errands', price: 149, currency: 'NOK' },
          { id: '6', name: 'Moving Help', description: 'Furniture moving', category: 'moving', price: 599, currency: 'NOK' },
        ])
      })
      .finally(() => setLoading(false))
  }, [locale])

  if (loading) {
    return <div style={{ padding: 48 }}>Loading...</div>
  }

  return (
    <div style={{ position: 'relative', minHeight: '100vh', padding: 48 }}>
      <Background active={activeCategory} />
      <div style={{ position: 'relative', zIndex: 10, maxWidth: 1200, margin: '0 auto' }}>
        <h2 style={{ textAlign: 'center', fontSize: 36, fontWeight: 700, marginBottom: 16, background: 'linear-gradient(90deg,#6366f1,#8b5cf6,#ec4899)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>Наши услуги</h2>
        <p style={{ textAlign: 'center', color: '#666', marginBottom: 48 }}>Выберите услугу. Наведите на карточку для эффекта.</p>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(300px,1fr))', gap: 24 }}>
          {services.map((service, index) => (
            <Card 
              key={service.id} 
              service={service} 
              index={index} 
              onHover={(c: Category) => setActiveCategory(c.id)} 
              onLeave={() => setActiveCategory('care')} 
            />
          ))}
        </div>
      </div>
    </div>
  )
}
