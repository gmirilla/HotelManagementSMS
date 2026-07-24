@props(['type', 'labels', 'datasets', 'options' => [], 'height' => 260])

{{--
    No wire:ignore here on purpose: the caller sets wire:key to whatever the
    chart's data depends on (branch, date range, ...). When that key changes,
    Livewire replaces this element outright and Alpine's x-init below runs
    again on the fresh <canvas>, building a new Chart.js instance from
    scratch. Simpler than wiring a manual update/destroy event bridge, and
    this dashboard's data only changes on user action (switching branch),
    not on a timer — a full remount is imperceptible here.
--}}
<div {{ $attributes }} x-data="{
        chart: null,
        init() {
            this.chart = new window.Chart(this.$refs.canvas, {
                type: @js($type),
                data: { labels: @js($labels), datasets: @js($datasets) },
                options: @js(array_replace_recursive(['responsive' => true, 'maintainAspectRatio' => false], $options)),
            });
        },
    }" x-init="init()">
    <canvas x-ref="canvas" style="height: {{ $height }}px"></canvas>
</div>
