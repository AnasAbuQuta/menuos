export const themes = {
  modern: { primary: '#176B52', secondary: '#D9A441', bg: '#F6F8F7', surface: '#FFFFFF', text: '#14231E', muted: '#61716B', border: '#D8E1DD', radius: '18px', buttonRadius: '10px', shadow: '0 12px 32px rgb(20 50 40 / 12%)' },
  minimal: { primary: '#232323', secondary: '#777777', bg: '#FAFAFA', surface: '#FFFFFF', text: '#171717', muted: '#6B6B6B', border: '#E5E5E5', radius: '6px', buttonRadius: '6px', shadow: '0 5px 18px rgb(0 0 0 / 7%)' },
  warm: { primary: '#B84A32', secondary: '#E5A84B', bg: '#FFF8EE', surface: '#FFFCF7', text: '#3B241C', muted: '#80675D', border: '#EBD7C4', radius: '22px', buttonRadius: '14px', shadow: '0 12px 30px rgb(92 45 25 / 13%)' },
  dark: { primary: '#7DD3AC', secondary: '#F0B95D', bg: '#111816', surface: '#1B2521', text: '#F3F7F5', muted: '#A9B8B2', border: '#34443E', radius: '16px', buttonRadius: '10px', shadow: '0 14px 36px rgb(0 0 0 / 35%)' },
  cafe: { primary: '#704B38', secondary: '#C69A69', bg: '#F6F0E8', surface: '#FFFDF9', text: '#30231C', muted: '#79685D', border: '#DED0C1', radius: '12px', buttonRadius: '8px', shadow: '0 10px 28px rgb(62 39 25 / 14%)' },
  fast_food: { primary: '#D72C24', secondary: '#FFC72C', bg: '#FFF7E8', surface: '#FFFFFF', text: '#271B17', muted: '#765E54', border: '#F0D4B3', radius: '14px', buttonRadius: '999px', shadow: '0 12px 30px rgb(151 34 27 / 15%)' },
}

export const themeKeys = Object.keys(themes)
export function themeFor(key) { return themes[key] || themes.modern }
export function themeVariables(key, primaryOverride = null) {
  const theme = themeFor(key)
  return { '--theme-primary': primaryOverride || theme.primary, '--theme-secondary': theme.secondary, '--theme-bg': theme.bg, '--theme-surface': theme.surface, '--theme-text': theme.text, '--theme-muted': theme.muted, '--theme-border': theme.border, '--theme-radius': theme.radius, '--theme-button-radius': theme.buttonRadius, '--theme-shadow': theme.shadow }
}
