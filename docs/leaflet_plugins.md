# Зум карты

Задается опцией `display.zoom_mode`
- native - родной для лифлета зум
- smooth - используется плавный зум
- slider - используется плагин `Leaflet.zoomslider` с контролом управления

# Leaflet.zoomslider - A zoom slider widget for leaflet

https://github.com/kartena/Leaflet.zoomslider

Не плавный зум для лифлета, но с виджетом

#  L.Control.ZoomBar

https://github.com/elrobis/L.Control.ZoomBar

Zoom-bar - не плавный, но с возможностью зумить регион


#  Leaflet.SmoothWheelZoom

https://github.com/mutsuyuki/Leaflet.SmoothWheelZoom

Плавный зум, но без каких-либо элементов управления. 

Мы его должны включать, если в настройках карты `display.zoom_mode` = `smooth`

# Плавный зум через `zoomSnap` - НЕ РАБОТАЕТ

Начиная с версии Leaflet 1.0 добавлен параметр `zoomSnap`

который в версии Leaflet 1.0.3+ed36a04 НЕ РАБОТАЕТ

```js
const map = L.map('map', {
    // 1 / 10th of the original zoom step
    zoomSnap: .1,
    // Faster debounce time while zooming
    wheelDebounceTime: 100
})
```

```css
.leaflet-zoom-anim .leaflet-zoom-animated {
    transition-timing-function: linear;
    transition-duration: 100ms;
}
```