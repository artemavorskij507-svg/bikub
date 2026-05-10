import { useCallback, useEffect, useRef, useState } from 'react'

interface SliderOptions {
  slideCount: number
  interval?: number
  autoplay?: boolean
}

interface SliderControls {
  currentIndex: number
  direction: 1 | -1
  isPlaying: boolean
  nextSlide: () => void
  previousSlide: () => void
  goToSlide: (index: number) => void
  pause: () => void
  resume: () => void
  handleKeyDown: (event: React.KeyboardEvent<HTMLElement>) => void
}

export function useSliderControls({ slideCount, interval = 5000, autoplay = true }: SliderOptions): SliderControls {
  const [currentIndex, setCurrentIndex] = useState(0)
  const [direction, setDirection] = useState<1 | -1>(1)
  const [isPlaying, setIsPlaying] = useState(autoplay)

  const timerRef = useRef<number | null>(null)
  const isHoveredRef = useRef(false)

  const clearTimer = useCallback(() => {
    if (timerRef.current) {
      window.clearTimeout(timerRef.current)
      timerRef.current = null
    }
  }, [])

  const scheduleNext = useCallback(() => {
    if (!isPlaying || isHoveredRef.current) return

    clearTimer()
    timerRef.current = window.setTimeout(() => {
      setDirection(1)
      setCurrentIndex((prev) => (prev + 1) % slideCount)
    }, interval)
  }, [clearTimer, interval, isPlaying, slideCount])

  const nextSlide = useCallback(() => {
    setDirection(1)
    setCurrentIndex((prev) => (prev + 1) % slideCount)
  }, [slideCount])

  const previousSlide = useCallback(() => {
    setDirection(-1)
    setCurrentIndex((prev) => (prev - 1 + slideCount) % slideCount)
  }, [slideCount])

  const goToSlide = useCallback((index: number) => {
    setDirection(index > currentIndex ? 1 : -1)
    setCurrentIndex(((index % slideCount) + slideCount) % slideCount)
  }, [currentIndex, slideCount])

  const pauseAutoplay = useCallback(() => {
    setIsPlaying(false)
    clearTimer()
  }, [clearTimer])

  const resumeAutoplay = useCallback(() => {
    setIsPlaying(true)
  }, [])

  const handleKeyDown = useCallback((event: React.KeyboardEvent<HTMLElement>) => {
    if (event.key === 'ArrowRight') {
      event.preventDefault()
      nextSlide()
    }
    if (event.key === 'ArrowLeft') {
      event.preventDefault()
      previousSlide()
    }
  }, [nextSlide, previousSlide])

  useEffect(() => {
    scheduleNext()
    return () => clearTimer()
  }, [clearTimer, scheduleNext, currentIndex])

  const handleVisibilityChange = useCallback(() => {
    if (document.hidden) {
      clearTimer()
    } else {
      scheduleNext()
    }
  }, [clearTimer, scheduleNext])

  useEffect(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)
    return () => document.removeEventListener('visibilitychange', handleVisibilityChange)
  }, [handleVisibilityChange])

  const setHoverState = useCallback((value: boolean) => {
    isHoveredRef.current = value
    if (value) {
      clearTimer()
    } else {
      scheduleNext()
    }
  }, [clearTimer, scheduleNext])

  return {
    currentIndex,
    direction,
    isPlaying,
    nextSlide,
    previousSlide,
    goToSlide,
    pause: () => {
      setHoverState(true)
      pauseAutoplay()
    },
    resume: () => {
      setHoverState(false)
      resumeAutoplay()
    },
    handleKeyDown
  }
}
