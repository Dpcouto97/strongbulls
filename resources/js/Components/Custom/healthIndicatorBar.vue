<template>
    <div class="health-bar-wrapper">
        <!-- Valores Marcos Acima da Barra \ Intervalos de valores -->
        <div class="health-bar-values-above">
            <span v-for="(marker, i) in markers" :key="'marker-' + i" :style="{ left: marker.position + '%' }">
                {{ marker.label }}
            </span>
        </div>

        <!-- Barra Colorida -->
        <div class="health-bar">
            <div
                v-for="(range, i) in normalizedRanges"
                :key="'range-' + i"
                class="bar-section"
                :style="{ width: range.width + '%', backgroundColor: range.color }"
            />
            <!-- Ponto de Marcacao do valor na barra -->
            <div class="pointer" :style="{ left: pointerPosition + '%' }">
                <div class="dot"></div>
            </div>
        </div>

        <!-- Section das Labels de baixo da barra -->
        <div class="health-bar-labels-under">
            <div
                v-for="(range, i) in normalizedRanges"
                :key="'label-' + i"
                class="label-under"
                :style="{ width: range.width + '%' }"
            >
                {{ range.label }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    value: { type: String, required: true },
    unit: { type: String, default: "" },
    ranges: { type: Array, required: true },
    minValue: { type: Number, default: null },
    maxValue: { type: Number, default: null },
});

const normalizedRanges = computed(() => {
    const segmentCount = props.ranges.length;
    const equalWidth = 100 / segmentCount;

    return props.ranges.map((r) => ({
        ...r,
        width: equalWidth,
    }));
});
const markers = computed(() => {
    const totalSegments = props.ranges.length;
    const segmentWidth = 100 / totalSegments;

    // Only show markers for all but the last range
    return props.ranges.slice(0, -1).map((r, i) => ({
        label: `${r.max}${props.unit}`,
        position: (i + 1) * segmentWidth,
    }));
});

const pointerPosition = computed(() => {
    const ranges = props.ranges;
    const min = props.minValue ?? 0;
    const max = props.maxValue ?? ranges[ranges.length - 1]?.max;

    // Clamp the value to the bar range
    const clampedValue = Math.min(Math.max(props.value, min), max);

    const totalSegments = ranges.length;

    let segmentIndex = 0;
    for (let i = 0; i < ranges.length; i++) {
        if (clampedValue <= ranges[i].max) {
            segmentIndex = i;
            break;
        }
    }

    const segmentWidth = 100 / totalSegments;
    const segmentStart = segmentWidth * segmentIndex;

    const lowerBound = segmentIndex === 0 ? min : ranges[segmentIndex - 1].max;
    const upperBound = ranges[segmentIndex]?.max;

    const segmentValuePercent =
        ((clampedValue - lowerBound) / (upperBound - lowerBound)) * segmentWidth;

    return segmentStart + segmentValuePercent;
});

</script>

<style scoped>
.health-bar-wrapper {
    width: 100%;
    max-width: 500px;
    margin: 20px auto;
    position: relative;
}

/* Value markers (above bar) */
.health-bar-values-above {
    position: relative;
    height: 20px;
    margin-bottom: 4px;
}

.health-bar-values-above span {
    position: absolute;
    transform: translateX(-50%);
    font-size: 10px;
    white-space: nowrap;
    color: #444;
}

/* The colored range bar */
.health-bar {
    display: flex;
    height: 15px;
    border-radius: 5px;
    overflow: hidden;
    position: relative;
}

.bar-section {
    height: 100%;
}

/* White Dot Pointer */
.pointer {
    position: absolute;
    top: 1.5px;
    transform: translateX(-50%);
    display: flex;
    justify-content: center;
    align-items: center;
    pointer-events: none;
}

.dot {
    width: 13px;
    height: 13px;
    background-color: white;
    border: 2px solid #333;
    border-radius: 50%;
}

/* Section labels under the bar */
.health-bar-labels-under {
    display: flex;
    margin-top: 6px;
    font-size: 12px;
    color: #444;
    text-align: center;
}

.label-under {
    flex-shrink: 0;
    text-align: center;
    white-space: nowrap;
    width: 100%;
}
</style>
