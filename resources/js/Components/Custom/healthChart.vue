<template>
    <div class="px-1">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined" style="font-size: 25px">analytics</span>
            <el-select
                v-model="selectedMetric"
                placeholder="Indicator"
                style="width: 240px"
                filterable
            >
                <el-option v-for="(label, key) in indicators" :key="key" :label="label" :value="key" />
            </el-select>
        </div>

        <!-- Chart -->
        <div class="chart-container">
            <Line :data="chartData" :options="chartOptions" />
        </div>

        <!-- Modal Detalhes da Avaliação -->
        <evaluation-details-modal
            ref="evaluationDetails"
            v-model:visible="showDetailsModal"
            :data="rowData"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler
} from "chart.js";
import EvaluationDetailsModal from "@/Components/Modals/Evaluation/evaluationDetailsModal.vue";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, LinearScale, CategoryScale, Filler);

//Defne o nome dado ao ficheiro
defineOptions({
    name: "healthChart",
});


const props = defineProps({
    evaluations: Array,
});

const $t = (key) => window.translations?.[key] || key;
const selectedMetric = ref("weight");
const rowData = ref(null);
const showDetailsModal = ref(false);

// Indicadores para o select
const indicators = {
    weight: $t('weight') + " (kg)",
    imc: "IMC",
    muscle_mass: $t('muscle_mass') + " (kg)",
    bone_mass: $t('bone_mass') + " (kg)",
    bmr: "BMR",
    visceral_fat: $t('visceral_fat'),
    body_fat: $t('body_fat') + " (%)",
    body_water: $t('body_water') + " (%)",
};

const chartData = computed(() => {
    const evaluations = props.evaluations ?? []; // fallback to empty array if undefined

    return {
        labels: evaluations.map((e) => e.date),
        datasets: [
            {
                label: indicators[selectedMetric.value],
                data: evaluations.map((e) => e[selectedMetric.value]),
                borderColor: "#1D3A32", // Cor da linha
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: "#FFD400", // Cor dos pontos
                fill: true,
                backgroundColor: "rgba(29, 58, 50, 0.2)", // Cor de fundo
                tension: 0.3,
            },
        ],
    };
});


const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    onClick: (event, elements, chart) => {
        if (elements.length > 0) {
            const pointIndex = elements[0].index;

            // Get the evaluation data based on index
            const selectedPoint = props.evaluations[pointIndex];

            // Chamo a funcao e envio o objeto.
            handleChartPointClick(selectedPoint);
        }
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                title: function (tooltipItems) {
                    const rawDate = tooltipItems[0].label;
                    const date = new Date(rawDate);

                    return date.toLocaleDateString("pt-PT", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric"
                    }) + " " + date.toLocaleTimeString("pt-PT", {
                        hour: "2-digit",
                        minute: "2-digit"
                    });
                },
                label: function (context) {
                    return `${indicators[selectedMetric.value]}: ${context.raw}`;
                }
            }
        }
    },
    scales: {
        y: {
            position: "right",
            beginAtZero: false,
        },
        x: {
            ticks: {
                callback: function (value) {
                    const dateStr = this.getLabelForValue(value);
                    const date = new Date(dateStr);
                    return date.toLocaleDateString("pt-PT", {
                        day: "2-digit",
                        month: "2-digit",
                    });
                },
                maxRotation: 0,
                autoSkip: true,
            },
        },
    },
};

function handleChartPointClick(data) {
    // Abro o modal de detalhes da avaliacao
    showDetailsModal.value = true;
    rowData.value = data;
}

</script>
<style scoped>
.chart-container {
    width: 100%;
    margin-top:15px;
    height: 50vh; /* 👈 Defino a altura do grafico em relacao ao ecra para ficar responsivo */
    position: relative;
}
</style>
