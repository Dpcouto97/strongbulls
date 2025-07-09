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
                <span class="material-symbols-outlined" style="font-size: 30px">info</span>
                <h2 class="text-2xl font-semibold text-gray-800">Details</h2>
            </div>
        </template>

        <!------------ DETALHES -------->
        <div class="details-container grid grid-cols-2 md:grid-cols-2 gap-x-1 gap-y-3">
            <!-- Product Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="productIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Product Name</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.name || "—" }}</div>
            </div>

            <!-- Duration -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="clockIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Duration</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.duration || "—" }}</div>
            </div>

            <!-- Operation Days -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="calendarIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Operation Days</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.operation_days || "—" }}</div>
            </div>

            <!-- Price -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="priceIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Price</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.price ? data.price + "€" : "—" }}</div>
            </div>

            <!-- Location -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="locationIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Location</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.location?.name || "—" }}</div>
            </div>

            <!-- Category -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="categoryIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Category</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.category ? data.category.name : "—" }}</div>
            </div>

            <!-- Attachments (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="attachmentIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Attachments</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">
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
                    <div v-else>—</div>
                </div>
            </div>

            <!-- Description (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="reportIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Product Description</span>
                </div>
                <div class="bg-gray-100 px-4 py-2 whitespace-pre-line">
                    {{ data.description || "—" }}
                </div>
            </div>

            <!-- Clientes -->
            <div class="providers-table col-span-1 md:col-span-2" v-if="data.clientsData.length > 0">
                <!-- Colunas -->
                <div class="grid grid-cols-4 gap-x-1 gap-y-3 w-full mt-6">
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="clientIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Clients</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="noVatIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Price w/o VAT</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="vatIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>VAT</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="priceIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Price</span>
                    </div>
                </div>

                <!-- Linhas com Info -->
                <div v-for="(client, index) in data.clientsData" :key="index" class="grid grid-cols-4 gap-1 mt-1">
                    <div class="bg-gray-100 px-4 py-2 flex items-center gap-2">
                        <!-- Client Name -->
                        <span>{{ client.name || "—" }}</span>
                    </div>
                    <div class="bg-gray-100 px-4 py-2">{{ getPriceWithoutVat(client) }}</div>
                    <div class="bg-gray-100 px-4 py-2">{{ getVatValue(client) }}</div>
                    <div class="bg-gray-100 px-4 py-2">{{ client.costData?.price + "€" || "—" }}</div>
                </div>
            </div>

            <!-- Fornecedores -->
            <div class="providers-table col-span-1 md:col-span-2" v-if="data.providersData.length > 0">
                <!-- Colunas -->
                <div class="grid grid-cols-4 gap-x-1 gap-y-3 w-full mt-6">
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="productIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Providers</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="noVatIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Price w/o VAT</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="vatIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>VAT</span>
                    </div>
                    <div class="label px-4 py-2 flex items-center gap-2">
                        <img :src="priceIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                        <span>Price</span>
                    </div>
                </div>

                <!-- Linhas com Info -->
                <div v-for="(provider, index) in data.providersData" :key="index" class="grid grid-cols-4 gap-1 mt-1">
                    <div class="bg-gray-100 px-4 py-2 flex items-center gap-2">
                        <!-- Colored Dot -->
                        <el-tooltip
                            class="item"
                            effect="dark"
                            :content="getPriorityName(provider.priority)"
                            :open-delay="1000"
                            placement="bottom-start"
                        >
                            <div class="priority-dot" :class="getPriorityClass(provider.priority)"></div>
                        </el-tooltip>
                        <!-- Provider Name -->
                        <span>{{ provider.name || "—" }}</span>
                    </div>
                    <div class="bg-gray-100 px-4 py-2">{{ getPriceWithoutVat(provider) }}</div>
                    <div class="bg-gray-100 px-4 py-2">{{ getVatValue(provider) }}</div>
                    <div class="bg-gray-100 px-4 py-2">{{ provider.costData?.price + "€" || "—" }}</div>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup>
import { nextTick, ref } from "vue";
import productIcon from "@/Icons/product.svg?url";
import categoryIcon from "@/Icons/car.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import calendarIcon from "@/Icons/calendar.svg?url";
import clockIcon from "@/Icons/clock.svg?url";
import priceIcon from "@/Icons/euro.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import vatIcon from "@/Icons/vat.svg?url";
import noVatIcon from "@/Icons/noVat.svg?url";
import clientIcon from "@/Icons/client.svg?url";
import usePriceCalculations from '@/Composables/usePriceCalculations.js';

// Define o nome do ficheiro
defineOptions({
    name: "productDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

const { getPriceWithoutVat, getVatValue } = usePriceCalculations();

//Define os emits
const emit = defineEmits(["update:visible"]);

const openModal = () => {
    // Garanto que o scroll reseta para o topo
    nextTick(() => {
        const dialogBody = document.querySelector(".details-body");
        if (dialogBody) {
            dialogBody.scrollTop = 0;
        }
    });
};

const closeModal = () => {
    emit("update:visible", false);
};

const getPriorityClass = (priority) => {
    // Função que devolve a class com a cor pretendida da prioridade.
    if (priority === 4) return "priority-veryhigh";
    if (priority === 3) return "priority-high";
    if (priority === 2) return "priority-medium";
    return "priority-low"; // default low
};

const getPriorityName = (priority) => {
    // Função que devolve o nome da prioridade
    if (priority === 4) return "Very High";
    if (priority === 3) return "High";
    if (priority === 2) return "Medium";
    return "Low"; // default low
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
.label {
    color: white;
    background-color: #596c92;
    font-weight: bold;
}

.details-modal .el-dialog__body {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}

.priority-low {
    background-color: #F9A171;
}

.priority-medium {
    background-color: #FFCC5C;
}

.priority-high {
    background-color: #C2FF3F;
}

.priority-veryhigh {
    background-color: #2DFF2B;
}

.priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 5px;
}
</style>
