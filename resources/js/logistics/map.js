export function initLogisticsMap(elementId) {
  const element = document.getElementById(elementId);
  if (!element) return;
  element.dataset.initialized = 'true';
}
