<script lang="ts">
  import type { GoodStats } from '../types';
  import { formatNumber } from '../utils/date';

  export let good: GoodStats;
</script>

<div class="good-card">
  <div class="good-header">
    <h3 class="good-id">#{good.good_id}</h3>
    <span class="reveals-badge">👁 {formatNumber(good.contact_reveals)} раскрытий</span>
  </div>
  <div class="good-stats">
    <div class="stat-item">
      <span class="stat-label">Просмотры</span>
      <span class="stat-value">{formatNumber(good.good_views)}</span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Контакты</span>
      <span class="stat-value reveals">{formatNumber(good.contact_reveals)}</span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Профиль</span>
      <span class="stat-value">{formatNumber(good.profile_views)}</span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Сообщения</span>
      <span class="stat-value">{formatNumber(good.message_sends)}</span>
    </div>
  </div>
</div>

<style>
  .good-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s, transform 0.15s;
  }

  .good-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    transform: translateY(-1px);
  }

  .good-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .good-id {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin: 0;
    font-family: monospace;
  }

  .reveals-badge {
    background: #fef3c7;
    color: #92400e;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 500;
  }

  .good-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }

  .stat-label {
    font-size: 0.7rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
  }

  .stat-value.reveals {
    color: #d97706;
  }
</style>
