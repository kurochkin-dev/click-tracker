<script lang="ts">
  import type { GeoEntry } from '../types';

  export let geo: GeoEntry[];
  export let title = 'Топ локаций';

  const countryFlags: Record<string, string> = {
    RS: '🇷🇸', UA: '🇺🇦', DE: '🇩🇪', US: '🇺🇸', GB: '🇬🇧',
    FR: '🇫🇷', PL: '🇵🇱', CA: '🇨🇦', AU: '🇦🇺', IT: '🇮🇹',
    ES: '🇪🇸', NL: '🇳🇱', GE: '🇬🇪', RU: '🇷🇺', TR: '🇹🇷',
  };

  $: maxEvents = Math.max(...geo.map(g => g.events), 1);
</script>

<div class="geo-chart">
  <h4>{title}</h4>
  {#if geo.length === 0}
    <p class="empty">Нет данных</p>
  {:else}
    <div class="geo-list">
      {#each geo as entry, i}
        {@const pct = Math.round((entry.events / maxEvents) * 100)}
        <div class="geo-row">
          <span class="rank">#{i + 1}</span>
          <span class="location">
            {#if entry.country}
              {countryFlags[entry.country] ?? '🌍'} {entry.country}
            {/if}
            {#if entry.city}
              <span class="city">— {entry.city}</span>
            {/if}
          </span>
          <div class="bar-wrap">
            <div class="bar" style="width: {pct}%"></div>
          </div>
          <span class="count">{entry.events.toLocaleString()}</span>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .geo-chart h4 {
    font-size: 0.875rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.75rem 0;
  }

  .geo-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .geo-row {
    display: grid;
    grid-template-columns: 2rem 10rem 1fr 4rem;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
  }

  .rank {
    color: #9ca3af;
    font-size: 0.75rem;
    text-align: right;
  }

  .location {
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .city {
    color: #9ca3af;
  }

  .bar-wrap {
    background: #f3f4f6;
    border-radius: 9999px;
    height: 6px;
    overflow: hidden;
  }

  .bar {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #d97706);
    border-radius: 9999px;
    transition: width 0.4s ease;
  }

  .count {
    text-align: right;
    font-weight: 600;
    color: #111827;
    font-size: 0.8rem;
  }

  .empty {
    color: #9ca3af;
    text-align: center;
    padding: 1rem;
  }
</style>
