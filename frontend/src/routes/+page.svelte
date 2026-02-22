<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';
  import { apiClient } from '$lib/api/client';
  import type { AllGoodsReport, DailyReport, GeoReport } from '$lib/types';
  import { getDefaultDateRange } from '$lib/utils/date';
  import DateRangePicker from '../lib/components/DateRangePicker.svelte';
  import GoodList from '../lib/components/GoodList.svelte';
  import GeoChart from '../lib/components/GeoChart.svelte';
  import StatsChart from '../lib/components/StatsChart.svelte';
  import LoadingSpinner from '../lib/components/LoadingSpinner.svelte';

  let loading = true;
  let error: string | null = null;
  let goodsReport: AllGoodsReport | null = null;
  let dailyReport: DailyReport | null = null;
  let geoReport: GeoReport | null = null;

  const defaultRange = getDefaultDateRange();
  let dateFrom: string | null = defaultRange.from;
  let dateTo: string | null = defaultRange.to;
  let isMounted = false;

  async function loadData() {
    if (!browser || !isMounted) return;
    loading = true;
    error = null;
    try {
      [goodsReport, dailyReport, geoReport] = await Promise.all([
        apiClient.getAllGoodsReport(dateFrom, dateTo),
        apiClient.getDailyReport(dateFrom, dateTo),
        apiClient.getGeoReport(dateFrom, dateTo)
      ]);
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to load data';
    } finally {
      loading = false;
    }
  }

  $: if (browser && isMounted && (dateFrom !== null || dateTo !== null)) {
    loadData();
  }

  onMount(() => {
    isMounted = true;
    loadData();
  });
</script>

<svelte:head>
  <title>Analytics Dashboard</title>
</svelte:head>

<div class="dashboard">
  <div class="dashboard-header">
    <div class="header-left">
      <h2>📊 Analytics Dashboard</h2>
      <span class="header-sub">Аналитика объявлений и контактов</span>
    </div>
    <DateRangePicker bind:dateFrom bind:dateTo />
  </div>

  {#if loading}
    <LoadingSpinner />
  {:else if error}
    <div class="error-message">
      <p>Ошибка: {error}</p>
      <button on:click={loadData} class="retry-button">Повторить</button>
    </div>
  {:else if goodsReport && dailyReport}
    <div class="dashboard-content">
      <!-- Общая статистика -->
      <div class="stats-section">
        <h3>Итого за период</h3>
        <div class="total-stats">
          <div class="total-stat-item">
            <span class="total-stat-label">Просмотры товаров</span>
            <span class="total-stat-value">{goodsReport.total.good_views.toLocaleString()}</span>
          </div>
          <div class="total-stat-item highlight">
            <span class="total-stat-label">Раскрытий контактов</span>
            <span class="total-stat-value"
              >{goodsReport.total.contact_reveals.toLocaleString()}</span
            >
          </div>
          <div class="total-stat-item">
            <span class="total-stat-label">Просмотры профилей</span>
            <span class="total-stat-value">{goodsReport.total.profile_views.toLocaleString()}</span>
          </div>
          <div class="total-stat-item">
            <span class="total-stat-label">Сообщений отправлено</span>
            <span class="total-stat-value">{goodsReport.total.message_sends.toLocaleString()}</span>
          </div>
        </div>
      </div>

      <!-- График активности -->
      <div class="chart-section">
        <h3>Активность по дням</h3>
        <div class="chart-wrapper">
          <StatsChart data={dailyReport.daily} type="line" title="Суточные события" />
        </div>
      </div>

      <!-- Гео + товары -->
      <div class="two-col">
        {#if geoReport}
          <div class="card">
            <h3>🌍 География</h3>
            <GeoChart geo={geoReport.geo} />
          </div>
        {/if}

        <div class="card">
          <h3>📦 Объявления ({goodsReport.goods.length})</h3>
          <GoodList goods={goodsReport.goods} />
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .dashboard {
    width: 100%;
  }

  .dashboard-header {
    background: white;
    padding: 1.25rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .header-left h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
  }

  .header-sub {
    font-size: 0.875rem;
    color: #9ca3af;
    display: block;
    margin-top: 0.2rem;
  }

  .dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .stats-section,
  .chart-section,
  .card {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .stats-section h3,
  .chart-section h3,
  .card h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
  }

  .total-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .total-stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.375rem;
    border: 1px solid #f3f4f6;
  }

  .total-stat-item.highlight {
    background: #fffbeb;
    border-color: #fcd34d;
  }

  .total-stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .total-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
  }

  .total-stat-item.highlight .total-stat-value {
    color: #d97706;
  }

  .chart-wrapper {
    margin-top: 0.5rem;
  }

  .two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
  }

  @media (max-width: 768px) {
    .two-col {
      grid-template-columns: 1fr;
    }
  }

  .error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    color: #991b1b;
  }

  .retry-button {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
  }

  .retry-button:hover {
    background: #2563eb;
  }
</style>
