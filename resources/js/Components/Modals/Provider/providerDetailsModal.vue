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

        <!---------- DETALHES ------>
        <div class="details-container grid grid-cols-2 md:grid-cols-2 gap-x-1 gap-y-3">
            <!-- Provider Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="userIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Provider Name</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.name || "—" }}</div>
            </div>

            <!-- CEO Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="userIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>CEO Name</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.ceo_name || "—" }}</div>
            </div>

            <!-- Email -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="emailIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Email</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ Array.isArray(data.email) ? data.email.join(', ') : data.email || "—" }}</div>
            </div>

            <!-- Phone Number -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="phoneIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Phone Number</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.phone_number || "—" }}</div>
            </div>

            <!-- Address -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="locationIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Address</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.address || "—" }}</div>
            </div>

            <!-- NIF -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="nifIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>NIF</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.nif || "—" }}</div>
            </div>

            <!-- IBAN -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="ibanIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>IBAN</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.iban || "—" }}</div>
            </div>

            <!-- Priority -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="priorityIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Priority</span>
                </div>
                <div v-if="data.priority === 1" class="bg-gray-100 px-4 py-2">Low</div>
                <div v-else-if="data.priority === 2" class="bg-gray-100 px-4 py-2">Medium</div>
                <div v-else-if="data.priority === 3" class="bg-gray-100 px-4 py-2">High</div>
                <div v-else-if="data.priority === 4" class="bg-gray-100 px-4 py-2">Very High</div>
                <div v-else class="bg-gray-100 px-4 py-2">{{ "—" }}</div>
            </div>

            <!-- Schedule -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="calendarIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Schedule</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.schedule || "—" }}</div>
            </div>

            <!-- Location -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="locationIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Location</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.location?.name || "—" }}</div>
            </div>

            <!-- VAT -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="vatIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>VAT</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.vat + "%" || "—" }}</div>
            </div>

            <!-- Payment policies (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="reportIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Payment Policies</span>
                </div>
                <div class="bg-gray-100 px-4 py-2 whitespace-pre-line">
                    {{ data.payment_policies || "—" }}
                </div>
            </div>

            <!-- Cancellation policies (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="reportIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Cancellation Policies</span>
                </div>
                <div class="bg-gray-100 px-4 py-2 whitespace-pre-line">
                    {{ data.cancellation_policies || "—" }}
                </div>
            </div>

            <!-- Categories (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="categoryIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Categories</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">
                    <div v-if="data.categoriesData?.length">
                        <div v-for="(category, index) in data.categoriesData" :key="index">
                            {{ category.name }}
                        </div>
                    </div>
                    <div v-else>—</div>
                </div>
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
        </div>
    </el-dialog>
</template>

<script setup>
import userIcon from "@/Icons/user.svg?url";
import categoryIcon from "@/Icons/car.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import calendarIcon from "@/Icons/calendar.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import emailIcon from "@/Icons/email.svg?url";
import phoneIcon from "@/Icons/phone.svg?url";
import nifIcon from "@/Icons/nif.svg?url";
import ibanIcon from "@/Icons/iban.svg?url";
import priorityIcon from "@/Icons/priority.svg?url";
import vatIcon from "@/Icons/vat.svg?url";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "providerDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

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
</style>
