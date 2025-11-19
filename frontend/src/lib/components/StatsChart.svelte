<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    LineController,
    BarElement,
    BarController,
    Title,
    Tooltip,
    Legend
  } from 'chart.js';
  import type { DailyStats } from '../types';

  Chart.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    LineController,
    BarElement,
    BarController,
    Title,
    Tooltip,
    Legend
  );

  export let data: DailyStats[] = [];
  export let type: 'line' | 'bar' = 'line';
  export let title: string = '';

  let chart: Chart | null = null;
  let canvas: HTMLCanvasElement;

  $: if (data && canvas) {
    updateChart();
  }

  function updateChart() {
    if (!canvas) return;

    if (chart) {
      chart.destroy();
    }

    const labels = data.map((d) => d.date);
    const clicksData = data.map((d) => d.clicks);
    const impressionsData = data.map((d) => d.impressions);

    chart = new Chart(canvas, {
      type,
      data: {
        labels,
        datasets: [
          {
            label: 'Clicks',
            data: clicksData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
          },
          {
            label: 'Impressions',
            data: impressionsData,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: !!title,
            text: title
          },
          legend: {
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  onMount(() => {
    if (canvas && data.length > 0) {
      updateChart();
    }
  });

  onDestroy(() => {
    if (chart) {
      chart.destroy();
    }
  });
</script>

<div class="chart-container">
  <canvas bind:this={canvas}></canvas>
</div>

<style>
  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
  }
</style>

