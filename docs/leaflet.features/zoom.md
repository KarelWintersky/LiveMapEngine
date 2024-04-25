# Зум карты

Задается опцией `display.zoom_mode`
- native - родной для лифлета зум
- smooth - используется плавный зум - плагин `Leaflet.SmoothWheelZoom`
- slider - используется плагин `Leaflet.zoomslider` с контролом управления

# Leaflet.zoomslider - A zoom slider widget for leaflet

https://github.com/kartena/Leaflet.zoomslider

Не плавный зум для лифлета, но с виджетом

#  Leaflet.SmoothWheelZoom

https://github.com/mutsuyuki/Leaflet.SmoothWheelZoom

Плавный зум, но без каких-либо элементов управления. 

Мы его должны включать, если в настройках карты `display.zoom_mode` = `smooth`

--- 
Не проверено:

#  L.Control.ZoomBar

https://github.com/elrobis/L.Control.ZoomBar

Zoom-bar - не плавный, но с возможностью зумить регион
