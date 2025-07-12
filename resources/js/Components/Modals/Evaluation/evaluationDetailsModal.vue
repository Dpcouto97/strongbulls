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

        <el-collapse v-model="activeCards">
            <el-collapse-item v-if="data.weight" title="Weight" name="1">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">scale</span>
                        <span class="title-text">Weight</span>
                        <span class="info-badge" :style="{ color: currentWeightColor}">
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
                            {{ data.imc}}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.imc"
                    :ranges="imcRanges"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.body_fat" title="Body Fat" name="3">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">body_fat</span>
                        <span class="title-text">Body Fat</span>
                        <span class="info-badge" :style="{ color: bodyFatValueColor }">
                            {{ data.body_fat + "%"}}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.body_fat"
                    unit="%"
                    :ranges="bodyFatRanges"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.visceral_fat" title="Visceral Fat" name="4">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">gastroenterology</span>
                        <span class="title-text">Visceral Fat</span>
                        <span class="info-badge" :style="{ color: visceralValueColor }">
                            {{ data.visceral_fat }}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.visceral_fat"
                    :ranges="visceralRanges"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.body_water" title="Body Water" name="5">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">humidity_high</span>
                        <span class="title-text">Body Water</span>
                        <span class="info-badge" :style="{ color: waterValueColor }">
                            {{ data.body_water + "%"}}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.body_water"
                    unit="%"
                    :ranges="waterRanges"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.muscle_mass" title="Muscle Mass" name="6">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">exercise</span>
                        <span class="title-text">Muscle Mass</span>
                        <span class="info-badge" :style="{ color: muscleMassColor }">
                            {{ data.muscle_mass + "Kg"}}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.muscle_mass"
                    unit="Kg"
                    :ranges="muscleMassRanges"
                />
            </el-collapse-item>
            <el-collapse-item v-if="data.bone_mass" title="Body Water" name="7">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">pet_supplies</span>
                        <span class="title-text">Bone Mass</span>
                        <span class="info-badge" :style="{ color: boneMassColor }">
                            {{ data.bone_mass + "Kg"}}
                        </span>
                    </div>
                </template>
                <health-indicator-bar
                    :value="data.bone_mass"
                    unit="Kg"
                    :ranges="boneMassRanges"
                />
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

const activeCards = ref([]);
const height = ref(null);
const weight = ref(null);
const imcRanges = [
    {
        label: "Underweight",
        max: 18.5,
        color: "#2563eb",
    },
    {
        label: "Normal",
        max: 25,
        color: "#059669",
    },
    {
        label: "Overweight",
        max: 30,
        color: "#f59e0b",
    },
    {
        label: "Obesity",
        max: 35,
        color: "#dc2626",
    },
];

const visceralRanges = [
    {
        label: "Excellent",
        max: 6,
        color: "#059669",
    },
    {
        label: "Acceptable",
        max: 11,
        color: "#10b981",
    },
    {
        label: "High",
        max: 15,
        color: "#f59e0b",
    },
    {
        label: "Excessive",
        max: 20,
        color: "#dc2626",
    },
];

const waterRanges = [
    {
        label: "Low",
        max: 50,
        color: "#2563eb",
    },
    {
        label: "Normal",
        max: 65,
        color: "#10b981",
    },
    {
        label: "High",
        max: 100,
        color: "#f59e0b",
    },
];

const bodyFatRanges = [
    {
        label: "Essential Fat",
        max: 6,
        color: "#2563eb",
    },
    {
        label: "Athletic",
        max: 13,
        color: "#059669",
    },
    {
        label: "In Shape",
        max: 17,
        color: "#10b981",
    },
    {
        label: "Acceptable",
        max: 25,
        color: "#6ee7b7",
    },
    {
        label: "High",
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

const muscleMassColor = computed(() =>
    getValueColor(props.data.muscle_mass, muscleMassRanges.value)
);

const boneMassColor = computed(() =>
    getValueColor(props.data.bone_mass, boneMassRanges.value)
);

const imcValueColor = computed(() => getValueColor(props.data.imc, imcRanges));
const waterValueColor = computed(() => getValueColor(props.data.body_water, waterRanges));
const visceralValueColor = computed(() => getValueColor(props.data.visceral_fat, visceralRanges));
const bodyFatValueColor = computed(() => getValueColor(props.data.body_fat, bodyFatRanges));

const getValueColor = (value, array) => {
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
    console.log('data',props.data);
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
            label: "Underweight",
            max: parseFloat((18.5 * h2).toFixed(2)),
            color: "#2563eb",
        },
        {
            label: "Normal",
            max: parseFloat((24.9 * h2).toFixed(2)),
            color: "#059669",
        },
        {
            label: "Overweight",
            max: parseFloat((29.9 * h2).toFixed(2)),
            color: "#f59e0b",
        },
        {
            label: "Obesity",
            max: parseFloat((40 * h2).toFixed(2)),
            color: "#dc2626",
        },
    ];
};

const generateMuscleMassRanges = (heightMeters) => {
    const h2 = heightMeters ** 2;

    return [
        {
            label: "Low",
            max: parseFloat((18 * h2).toFixed(2)),
            color: "#2563eb",
        },
        {
            label: "Normal",
            max: parseFloat((22 * h2).toFixed(2)),
            color: "#10b981",
        },
        {
            label: "High",
            max: parseFloat((26 * h2).toFixed(2)),
            color: "#059669",
        },
    ];
};

const generateBoneMassRanges = (heightMeters) => {
    const base = 22 * (heightMeters ** 2)

    return [
        {
            label: "Low",
            max: parseFloat((base * 0.03).toFixed(2)),
            color: "#f59e0b",
        },
        {
            label: "Normal",
            max: parseFloat((base * 0.045).toFixed(2)),
            color: "#10b981",
        },
        {
            label: "High",
            max: parseFloat((base * 0.06).toFixed(2)),
            color: "#059669",
        },
    ];
};



const downloadAttachment = (file) => {
    // Download do ficheiro
    const link = document.createElement("a");
    link.href = file.url;
    link.download = file.name || "attachment";
    link.click();
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
</style>
