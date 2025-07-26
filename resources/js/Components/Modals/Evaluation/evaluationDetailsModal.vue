<template>
    <el-dialog
        :model-value="visible"
        @open="openModal"
        @close="closeModal"
        class="details-modal"
        body-class="details-body"
        top="2vh"
    >
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">health_metrics</span>
                <h2 class="text-2xl font-semibold text-gray-800">{{ data.client.name }}</h2>
            </div>
        </template>

        <!-- Data da avaliacao -->
        <div class="date-container">
            <div class="date-display">{{ formatDateTime(data.date) }}</div>
        </div>

        <!-- Ultima Avaliacao Comparacao -->
        <div class="last-evaluation-container" v-if="data.last_evaluation_compare">
            <div class="last-date-container">
                {{ $t('since') + formatDateTime(data.last_evaluation_compare?.date) }}
            </div>
            <div class="compare-values-container">
                <div class="compare-box" v-if="data.weight">
                    <span class="value">{{ getDifference(data.weight, data.last_evaluation_compare.weight) }}</span>
                    <span class="unit">kg</span>
                    <div class="label">{{ $t('weight') }}</div>
                </div>
                <div class="compare-box" v-if="data.imc">
                    <span class="value">{{ getDifference(data.imc, data.last_evaluation_compare.imc) }}</span>
                    <div class="label">IMC</div>
                </div>
                <div class="compare-box" v-if="data.body_fat">
                    <span class="value">{{ getDifference(data.body_fat, data.last_evaluation_compare.body_fat) }}</span>
                    <span class="unit">%</span>
                    <div class="label">{{ $t('body_fat') }}</div>
                </div>
                <div class="compare-box" v-if="data.muscle_mass">
                    <span class="value">
                        {{ getDifference(data.muscle_mass, data.last_evaluation_compare.muscle_mass) }}
                    </span>
                    <span class="unit">kg</span>
                    <div class="label">{{ $t('muscle_mass') }}</div>
                </div>
            </div>
        </div>

        <el-collapse v-model="activeCards">
            <el-collapse-item v-if="data.weight" title="Weight" name="1">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">scale</span>
                        <span class="title-text">{{ $t('weight') }}</span>
                        <span class="info-badge" :style="{ color: currentWeightColor }">
                            {{ data.weight + " Kg" }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="weight"
                    unit="kg"
                    :ranges="weightRanges"
                    :min-value="weightMin"
                    :max-value="weightMax"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.imc" title="IMC" name="2">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">straighten</span>
                        <span class="title-text">IMC</span>
                        <span class="info-badge" :style="{ color: imcValueColor }">
                            {{ data.imc }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.imc" :ranges="imcRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.body_fat" title="Body Fat" name="3">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">body_fat</span>
                        <span class="title-text">{{ $t('body_fat') }}</span>
                        <span class="info-badge" :style="{ color: bodyFatValueColor }">
                            {{ data.body_fat + "%" }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.body_fat" unit="%" :ranges="bodyFatRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.visceral_fat" title="Visceral Fat" name="4">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">gastroenterology</span>
                        <span class="title-text">{{ $t('visceral_fat') }}</span>
                        <span class="info-badge" :style="{ color: visceralValueColor }">
                            {{ data.visceral_fat }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.visceral_fat" :ranges="visceralRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.body_water" title="Body Water" name="5">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">humidity_high</span>
                        <span class="title-text">{{ $t('body_water') }}</span>
                        <span class="info-badge" :style="{ color: waterValueColor }">
                            {{ data.body_water + "%" }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.body_water" unit="%" :ranges="waterRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.muscle_mass" title="Muscle Mass" name="6">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">exercise</span>
                        <span class="title-text">{{ $t('muscle_mass') }}</span>
                        <span class="info-badge" :style="{ color: muscleMassColor }">
                            {{ data.muscle_mass + "Kg" }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.muscle_mass" unit="Kg" :ranges="muscleMassRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.bone_mass" title="Body Water" name="7">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">pet_supplies</span>
                        <span class="title-text">{{ $t('bone_mass') }}</span>
                        <span class="info-badge" :style="{ color: boneMassColor }">
                            {{ data.bone_mass + "Kg" }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar :value="data.bone_mass" unit="Kg" :ranges="boneMassRanges" />
            </el-collapse-item>
            <el-collapse-item v-if="data.description" title="Description" name="8">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">article</span>
                        <span class="title-text">{{ $t('description') }}</span>
                    </div>
                </template>
                {{ data.description }}
            </el-collapse-item>
            <el-collapse-item v-if="data.attachments.length > 0" title="Attachments" name="9">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">attach_file</span>
                        <span class="title-text">{{ $t('attachments') }}</span>
                    </div>
                </template>
                <div v-if="data.attachments?.length">
                    <div
                        v-for="(file, index) in data.attachments"
                        :key="index"
                        class="underline cursor-pointer"
                        @click="downloadAttachment(file)"
                    >
                        {{ file.name }}
                    </div>
                </div>
            </el-collapse-item>
        </el-collapse>
    </el-dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import healthIndicatorBar from "@/Components/Custom/healthIndicatorBar.vue";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "evaluationDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

//Define os emits
const emit = defineEmits(["update:visible"]);

const $t = (key) => window.translations?.[key] || key;
const activeCards = ref([]);
const height = ref(null);
const weight = ref(null);
const imcRanges = [
    {
        label: $t('underweight'),
        max: 18.5,
        color: "#2563eb",
    },
    {
        label: "Normal",
        max: 25,
        color: "#059669",
    },
    {
        label: $t('overweight'),
        max: 30,
        color: "#f59e0b",
    },
    {
        label: $t('obesity'),
        max: 35,
        color: "#dc2626",
    },
];

const visceralRanges = [
    {
        label: $t('excellent'),
        max: 6,
        color: "#059669",
    },
    {
        label: $t('acceptable'),
        max: 11,
        color: "#10b981",
    },
    {
        label: $t('high'),
        max: 15,
        color: "#f59e0b",
    },
    {
        label: $t('excessive'),
        max: 20,
        color: "#dc2626",
    },
];

const waterRanges = [
    {
        label: $t('low'),
        max: 50,
        color: "#2563eb",
    },
    {
        label: "Normal",
        max: 65,
        color: "#10b981",
    },
    {
        label: $t('high'),
        max: 100,
        color: "#f59e0b",
    },
];

const bodyFatRanges = [
    {
        label: $t('essential_fat'),
        max: 6,
        color: "#2563eb",
    },
    {
        label: $t('athletic'),
        max: 13,
        color: "#059669",
    },
    {
        label: $t('in_shape'),
        max: 17,
        color: "#10b981",
    },
    {
        label: $t('acceptable'),
        max: 25,
        color: "#6ee7b7",
    },
    {
        label: $t('high'),
        max: 32,
        color: "#dc2626",
    },
];

const weightRanges = computed(() => {
    if (!height.value) return [];
    return generateWeightRanges(height.value);
});

const weightMax = computed(() => {
    const ranges = weightRanges.value;
    if (!ranges.length) return 100; // default fallback
    return ranges[ranges.length - 1].max;
});

const weightMin = computed(() => {
    const ranges = weightRanges.value;
    if (!ranges.length) return 0;
    return Math.max(0, ranges[0].max - 10);
});

const currentWeightColor = computed(() => {
    const w = weight.value;
    if (!w || !weightRanges.value.length) return "#ccc";

    for (const range of weightRanges.value) {
        if (w <= range.max) {
            return range.color;
        }
    }
    return weightRanges.value[weightRanges.value.length - 1].color;
});

const muscleMassRanges = computed(() => {
    if (!height.value) return [];
    return generateMuscleMassRanges(height.value);
});

const boneMassRanges = computed(() => {
    if (!height.value) return [];
    return generateBoneMassRanges(height.value);
});

const muscleMassColor = computed(() => getValueColor(props.data.muscle_mass, muscleMassRanges.value));

const boneMassColor = computed(() => getValueColor(props.data.bone_mass, boneMassRanges.value));

const imcValueColor = computed(() => getValueColor(props.data.imc, imcRanges));
const waterValueColor = computed(() => getValueColor(props.data.body_water, waterRanges));
const visceralValueColor = computed(() => getValueColor(props.data.visceral_fat, visceralRanges));
const bodyFatValueColor = computed(() => getValueColor(props.data.body_fat, bodyFatRanges));

const formatDateTime = (rawDate) => {
    // Devolve a data com o formato : July 7, 2025 10:50 AM
    if (!rawDate) return "";
    const date = new Date(rawDate);
    return date.toLocaleString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

const getValueColor = (value, array) => {
    // Retorna a cor
    if (!value || !array.length) return "#ccc";

    for (const range of array) {
        if (value <= range.max) {
            return range.color;
        }
    }
    return array[array.length - 1].color;
};

const openModal = () => {
    // Garanto que o scroll reseta para o topo
    nextTick(() => {
        const dialogBody = document.querySelector(".details-body");
        if (dialogBody) {
            dialogBody.scrollTop = 0;
        }
        // Atribuo os valores da altura e peso do cliente dessa mesma avaliacao
        height.value = props.data?.client?.height / 100;
        weight.value = props.data?.weight;
    });
};

const closeModal = () => {
    activeCards.value = [];
    emit("update:visible", false);
};

const generateWeightRanges = (heightMeters) => {
    const h2 = heightMeters ** 2;

    return [
        {
            label: $t('underweight'),
            max: parseFloat((18.5 * h2).toFixed(2)),
            color: "#2563eb",
        },
        {
            label: "Normal",
            max: parseFloat((24.9 * h2).toFixed(2)),
            color: "#059669",
        },
        {
            label: $t('overweight'),
            max: parseFloat((29.9 * h2).toFixed(2)),
            color: "#f59e0b",
        },
        {
            label: $t('obesity'),
            max: parseFloat((40 * h2).toFixed(2)),
            color: "#dc2626",
        },
    ];
};

const generateMuscleMassRanges = (heightMeters) => {
    const h2 = heightMeters ** 2;

    return [
        {
            label: $t('low'),
            max: parseFloat((18 * h2).toFixed(2)),
            color: "#2563eb",
        },
        {
            label: "Normal",
            max: parseFloat((22 * h2).toFixed(2)),
            color: "#10b981",
        },
        {
            label: $t('high'),
            max: parseFloat((26 * h2).toFixed(2)),
            color: "#059669",
        },
    ];
};

const generateBoneMassRanges = (heightMeters) => {
    const base = 22 * heightMeters ** 2;

    return [
        {
            label: $t('low'),
            max: parseFloat((base * 0.03).toFixed(2)),
            color: "#f59e0b",
        },
        {
            label: "Normal",
            max: parseFloat((base * 0.045).toFixed(2)),
            color: "#10b981",
        },
        {
            label: $t('high'),
            max: parseFloat((base * 0.06).toFixed(2)),
            color: "#059669",
        },
    ];
};

const getDifference = (newValue, oldValue) => {
    if (newValue == null || oldValue == null || isNaN(newValue) || isNaN(oldValue)) return "";
    const diff = parseFloat((newValue - oldValue).toFixed(2));
    if (diff > 0) return `+${diff}`;
    if (diff < 0) return `${diff}`; // already negative
    return "0";
};

const downloadAttachment = (file) => {
    // If file.url is a Blob URL (from local upload)
    if (file.raw && file.url?.startsWith("blob:")) {
        const blob = file.raw;
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = file.name || "attachment";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url); // Clean up
    }
    // If file.url is from server (http/https)
    else if (file.url?.startsWith("http") || file.url?.startsWith("/")) {
        const link = document.createElement("a");
        link.href = file.url;
        link.download = file.name || "attachment";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } else {
        ElNotification({
            title: "Error",
            message: "File not available for download.",
            type: "error",
        });
    }
};

</script>

<style>
.details-modal .el-dialog__body {
    height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}

.custom-title {
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: bold;
}

.custom-title .material-symbols-outlined {
    font-size: 22px;
}

.title-text {
    flex-grow: 1;
}

.info-badge {
    font-size: 16px;
    padding: 2px 10px;
    border-radius: 10px;
}

/* Remove border azul estranha no input das datas */
.el-date-editor .el-range-input {
    box-shadow: none !important;
    border-color: transparent !important;
}

/* Css para mostrar barra cinzenta com data atual */
.date-container .date-display {
    width: 100%;
    background-color: #f3f4f6; /* Tailwind gray-100 */
    color: #374151; /* Tailwind gray-700 */
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    margin-bottom: 15px;
}

.last-evaluation-container {
    background-color: #fff;
    padding: 5px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-family: sans-serif;
}

.last-date-container {
    font-size: 13px;
    color: #9ca3af;
    margin-bottom: 10px;
}

/* Css para mostrar comparacao de valores e data da ultima avaliacao */
.compare-values-container {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    text-align: center;
}

.compare-box {
    flex: 1;
}

.compare-box .value {
    font-weight: 600;
    font-size: 20px;
    color: #111827; /* Tailwind gray-900 */
}

.compare-box .unit {
    font-size: 12px;
    margin-left: 2px;
    color: #6b7280; /* Tailwind gray-500 */
}

.compare-box .label {
    font-size: 12px;
    font-weight:300;
    color: #6b7280;
    background-color:transparent !important;
}
</style>
