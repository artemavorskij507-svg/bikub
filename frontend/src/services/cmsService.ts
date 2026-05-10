import type { VideoSlide } from '@/components/Hero/VideoSlider'
import type { ServiceCardData } from '@/components/Services/ServiceCard'

const CMS_BASE_URL = process.env.NEXT_PUBLIC_CMS_BASE_URL
const CACHE_TTL = 1000 * 60 * 5 // 5 minutes

type CacheRecord<T> = {
  data: T
  timestamp: number
}

const cache = new Map<string, CacheRecord<unknown>>()

async function fetchFromCMS<T>(endpoint: string, fallback: T): Promise<T> {
  if (!CMS_BASE_URL) {
    return fallback
  }

  const cacheKey = endpoint
  const now = Date.now()
  const cached = cache.get(cacheKey) as CacheRecord<T> | undefined

  if (cached && now - cached.timestamp < CACHE_TTL) {
    return cached.data
  }

  try {
    const response = await fetch(`${CMS_BASE_URL}${endpoint}`, {
      headers: {
        Accept: 'application/json'
      },
      next: { revalidate: CACHE_TTL / 1000 }
    })

    if (!response.ok) {
      throw new Error(`CMS request failed: ${response.status}`)
    }

    const data = (await response.json()) as T
    cache.set(cacheKey, { data, timestamp: now })
    return data
  } catch {
    return fallback
  }
}

export const VIDEO_SLIDES_FALLBACK: VideoSlide[] = [
  {
    id: 'grocery-delivery',
    title: 'Доставка покупок и продуктов за 90 минут',
    subtitle: 'Закупим, проверим качество и доставим прямо к вашей двери. Экономим ваше время в ритме Нарвика.',
    eyebrow: 'GLF Deliveri',
    ctaLabel: 'Быстрый заказ',
    ctaHref: '/order?service=delivery',
    secondaryCtaLabel: 'Варианты доставки',
    secondaryCtaHref: '/catalog?category=delivery',
    videoUrl: 'https://cdn.glfbikube.no/hero/grocery-delivery.mp4',
    posterUrl: 'https://cdn.glfbikube.no/hero/grocery-delivery.jpg',
    accentColor: '#22d3ee'
  },
  {
    id: 'bulky',
    title: 'Крупногабаритная доставка за один визит',
    subtitle: 'Бригада курьеров, такелаж, подъем на этаж и сборка — под ключ и со страховкой.',
    eyebrow: 'Bulky Hub',
    ctaLabel: 'Рассчитать стоимость',
    ctaHref: '/order?service=bulky',
    secondaryCtaLabel: 'Подробнее о сервисе',
    secondaryCtaHref: '/catalog?category=bulky',
    videoUrl: 'https://cdn.glfbikube.no/hero/bulky-delivery.mp4',
    posterUrl: 'https://cdn.glfbikube.no/hero/bulky-delivery.jpg',
    accentColor: '#f97316'
  },
  {
    id: 'food',
    title: 'Готовая еда от локальных ресторанов',
    subtitle: 'Оптимальные ETA, сохранение температуры и бонусная программа для постоянных клиентов.',
    eyebrow: 'Food Express',
    ctaLabel: 'Выбрать ресторан',
    ctaHref: '/catalog?category=food',
    secondaryCtaLabel: 'Стать партнером',
    secondaryCtaHref: '/partnership',
    videoUrl: 'https://cdn.glfbikube.no/hero/food-delivery.mp4',
    posterUrl: 'https://cdn.glfbikube.no/hero/food-delivery.jpg',
    accentColor: '#facc15'
  }
]

export async function getVideoSlides(): Promise<VideoSlide[]> {
  return fetchFromCMS<VideoSlide[]>('/homepage/video-slides', VIDEO_SLIDES_FALLBACK)
}

export type HomePageData = {
  videoSlides: VideoSlide[]
  services: ServiceCardData[]
}

export async function getHomePageData(): Promise<HomePageData> {
  const [videoSlides, services] = await Promise.all([getVideoSlides(), getServices()])
  return {
    videoSlides,
    services
  }
}

// Placeholder functions for future phases
export const SERVICES_FALLBACK: ServiceCardData[] = [
  {
    id: 'grocery',
    title: 'Персональный шоппер',
    description: 'Закупка по списку, умные замены, оптимизация чеков и ETA в реальном времени.',
    icon: '🛒',
    accent: '#22d3ee',
    tags: ['90 минут', 'AI substitutions', 'ETA'],
    ctaLabel: 'Заказать продукты',
    href: '/catalog?category=delivery'
  },
  {
    id: 'bulky',
    title: 'Bulky Logistics',
    description: 'Бригада, подъем, упаковка и сборка крупногабарита по SLA GLF.',
    icon: '📦',
    accent: '#f97316',
    tags: ['Такелаж', 'Страхование', 'Crew'],
    ctaLabel: 'Рассчитать доставку',
    href: '/catalog?category=bulky'
  },
  {
    id: 'food',
    title: 'Express Food',
    description: 'Подключенные рестораны Нарвика, контроль температуры и лояльность.',
    icon: '🍜',
    accent: '#facc15',
    tags: ['Температура', 'Лояльность', 'HORECA'],
    ctaLabel: 'Выбрать ресторан',
    href: '/catalog?category=food'
  },
  {
    id: 'handyman',
    title: 'Мастер GLF',
    description: 'Сертифицированные специалисты, постоплата и гарантия 24 часа.',
    icon: '🛠️',
    accent: '#8b5cf6',
    tags: ['Гарантия', 'Сборка', 'Ремонт'],
    ctaLabel: 'Найти мастера',
    href: '/catalog?category=handyman'
  },
  {
    id: 'eco',
    title: 'Eco Impact',
    description: 'Утилизация, сортировка и отчеты для бизнеса, партнёрство с переработчиками.',
    icon: '🌱',
    accent: '#34d399',
    tags: ['Zero waste', 'Отчеты', 'B2B'],
    ctaLabel: 'Заказать эко-сервис',
    href: '/catalog?category=eco'
  },
  {
    id: 'roadside',
    title: 'Roadside Assist',
    description: 'Помощь на дороге 24/7: эвакуация, зарядка, быстрый ремонт.',
    icon: '🚗',
    accent: '#f87171',
    tags: ['24/7', 'EV-ready', 'Telematics'],
    ctaLabel: 'Вызвать помощь',
    href: '/catalog?category=roadside'
  },
  {
    id: 'personal',
    title: 'Personal Concierge',
    description: 'Поручения, доставка документов, персональные сценарии и бизнес-аккаунты.',
    icon: '🤝',
    accent: '#60a5fa',
    tags: ['B2B', 'Personal', 'SLA'],
    ctaLabel: 'Узнать возможности',
    href: '/catalog?category=personal'
  }
]

export async function getServices() {
  return fetchFromCMS<ServiceCardData[]>('/homepage/services', SERVICES_FALLBACK)
}

export async function getTestimonials() {
  return fetchFromCMS('/homepage/testimonials', [])
}

export async function getNews() {
  return fetchFromCMS('/homepage/news', [])
}

export async function getSettings() {
  return fetchFromCMS('/homepage/settings', {})
}
