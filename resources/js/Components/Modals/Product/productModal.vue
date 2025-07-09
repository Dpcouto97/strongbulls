<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800 dialog-title">
                    {{ modalTitle }}
                </h2>
            </div>
        </template>

        <!-- ABAS -->
        <div class="selection-tabs-wrapper">
            <div class="selection-tabs">
                <el-button
                    v-for="tab in tabs"
                    :key="tab.name"
                    class="tab"
                    :class="{ active: selectedTab === tab.name }"
                    @click="selectedTab = tab.name"
                    size="small"
                >
                    {{ tab.label }}
                </el-button>
            </div>
        </div>

        <!-- FORMULARIO -->
        <el-form
            v-show="selectedTab === 'general'"
            :model="form"
            :rules="rules"
            ref="formRef"
            label-position="top"
            class="space-y-5"
        >
            <!-- Product Name -->
            <el-form-item label="Product Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="productIcon" alt="icon" class="w-4 h-4" />
                        <span>Product Name</span>
                    </div>
                </template>
                <el-input v-model="form.name" placeholder="Write here..." />
            </el-form-item>

            <!-- Product Description -->
            <el-form-item label="Product Description" prop="description">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="reportIcon" alt="icon" class="w-4 h-4" />
                        <span>Product Description</span>
                    </div>
                </template>
                <el-input
                    class="no-resize-textarea"
                    type="textarea"
                    :rows="2"
                    v-model="form.description"
                    placeholder="Write here..."
                />
            </el-form-item>

            <!-- Operations Days -->
            <el-form-item label="Operations Days" prop="operation_days">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="calendarIcon" alt="icon" class="w-4 h-4" />
                        <span>Operation Days</span>
                    </div>
                </template>
                <el-input v-model="form.operation_days" placeholder="Write here..." />
            </el-form-item>

            <!-- Duration -->
            <el-form-item label="Duration" prop="duration">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="clockIcon" alt="icon" class="w-4 h-4" />
                        <span>Duration</span>
                    </div>
                </template>
                <el-input v-model="form.duration" placeholder="Write here..." />
            </el-form-item>

            <!-- Category -->
            <el-form-item label="Category" prop="category_id">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="categoryIcon" alt="icon" class="w-4 h-4" />
                        <span>Category</span>
                    </div>
                </template>
                <el-select v-model="form.category_id" placeholder="Choose Category" clearable filterable>
                    <el-option v-for="item in categoriesList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- Location -->
            <el-form-item label="Location" prop="location_id">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="locationIcon" alt="icon" class="w-4 h-4" />
                        <span>Location</span>
                    </div>
                </template>
                <el-select v-model="form.location_id" placeholder="Choose Location" clearable filterable>
                    <el-option v-for="item in locationsList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- ATTACHMENTS -->
            <el-form-item label="Attachments" prop="fileList">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="attachmentIcon" alt="icon" class="w-4 h-4" />
                        <span>Attachments</span>
                    </div>
                </template>
                <files-upload ref="filesUploadRef"></files-upload>
            </el-form-item>
        </el-form>

        <!-- Preços dos Clientes - Componente -->
        <product-clients-prices
            v-show="selectedTab === 'client'"
            ref="clientsPricesRef"
            :data="row"
            :edit-mode="editMode"
            :clients-list="clientsList"
        ></product-clients-prices>

        <!-- Custos dos Fornecedores - Componente -->
        <product-providers-costs
            v-show="selectedTab === 'provider'"
            ref="providersCostsRef"
            :data="row"
            :edit-mode="editMode"
            :providers-list="providersList"
        ></product-providers-costs>

        <div v-show="selectedTab === 'margins'">Margens</div>

        <!-- Botao GUARDAR O MODAL-->
        <template #footer>
            <el-button
                v-loading="isLoading"
                type="primary"
                size="large"
                class="w-full text-white save-button"
                @click="submitUpdate"
                :disabled="isLoading"
            >
                {{ editMode ? "SAVE CHANGES" : "CREATE PRODUCT" }}
            </el-button>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, nextTick, computed, watch } from "vue";
import usePriceCalculations from "@/Composables/usePriceCalculations.js";
import { ElNotification } from "element-plus";
import axios from "axios";
import "../../../../css/form.css";
import filesUpload from "@/Components/Custom/filesUpload.vue";
import productIcon from "@/Icons/product.svg?url";
import categoryIcon from "@/Icons/car.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import calendarIcon from "@/Icons/calendar.svg?url";
import clockIcon from "@/Icons/clock.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import ProductClientsPrices from "@/Components/Modals/Product/productClientsPrices.vue";
import ProductProvidersCosts from "@/Components/Modals/Product/productProvidersCosts.vue";

// Define o nome do ficheiro
defineOptions({
    name: "ProductModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    row: Object,
    editMode: Boolean,
    // Listas para Selects
    categoriesList: Array,
    locationsList: Array,
    providersList: Array,
    clientsList: Array,
});

//Define os emits
const emit = defineEmits(["update:visible", "update"]);

// Ref - define uma variavel para uso geral no componente
// Variaveis Reativas
const formRef = ref(null);
const clientsPricesRef = ref(null);
const providersCostsRef = ref(null);
const filesUploadRef = ref(null);
const isLoading = ref(false);
const form = ref({
    id: "",
    name: "",
    description: "",
    duration: "",
    operation_days: "",
    location_id: null,
    category_id: null,
    attachments: [],
});

const selectedTab = ref("general");
const tabs = [
    { name: "general", label: "GENERAL" },
    { name: "client", label: "CLIENT PRICES" },
    { name: "provider", label: "PROVIDER PRICES" },
    { name: "margins", label: "MARGINS" },
];

// Como as rules nao sao reactivas/nao sao alteradas entao nao precisamos de criar com 'ref'
const rules = {
    name: [
        { required: false, message: "Please input a Name", trigger: "blur" },
        {
            min: 1,
            max: 50,
            message: "Length should be 1 to 100",
            trigger: "blur",
        },
    ],
    price: [{ required: false, message: "Please choose a price", trigger: "blur" }],
};

const modalTitle = computed(() => {
    // Checka se está em modo de ediçao
    if (props.editMode) {
        // Se a selectedTab for qualquer uma excepto "general", mostro "Edit Product - %name%"
        if (selectedTab.value !== "general" && form.value.name) {
            return `Edit Product - ${form.value.name}`;
        }
        // Se for a general tab entao mostro so "Edit Product"
        return "Edit Product";
    }
    // senão "Create Product"
    return "Create Product";
});

const openModal = () => {
    // Ao abrir o modal se estou em modo de edicao e tenho os dados preencho o formulario.
    if (props.editMode && props.row) {
        form.value = JSON.parse(JSON.stringify(props.row)); // Crio uma copia segura sem referencias ao prop original.
    }

    // Defino os dados dos anexos no componente do mesmo.
    nextTick(() => {
        // Garantir que na abertura do modal, o scroll fica sempre no topo
        scrollToTop();

        // Defino os ficheiros no componente de acordo com os dados recebidos no modal.
        filesUploadRef.value?.setFiles(form.value.attachments || []);

        // Faço load dos dados nas devidas tabs do modal
        clientsPricesRef.value?.loadData();
        providersCostsRef.value?.loadData();
    });
};

const scrollToTop = () => {
    const formEl = formRef.value?.$el;
    if (formEl) {
        if (typeof formEl.scrollTo === "function") {
            formEl.scrollTo({ top: 0, behavior: "instant" });
        } else {
            formEl.scrollTop = 0;
        }
    }
};

const closeModal = () => {
    formRef.value?.clearValidate();
    filesUploadRef.value?.resetUpload();
    clientsPricesRef.value?.clearData();
    providersCostsRef.value?.clearData();
    resetForm();
    emit("update:visible", false);
};

const submitUpdate = async () => {
    try {
        isLoading.value = true;
        //Como o "validate' não é assincrono  é preciso usar promise para esperar pela resposta do validate.
        const valid = await new Promise((resolve) => {
            formRef.value.validate((valid) => {
                resolve(valid);
            });
        });

        if (valid) {
            // Necessário criar este objeto formData que permite a passagem de ficheiros.
            const formData = new FormData();

            // Funcao para verificar se os ids caso seja null vao como null e nao string (ex. no location_id, e category_id)
            const appendIfDefined = (key, value) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            };

            // Form Fields
            formData.append("id", form.value.id);
            formData.append("name", form.value.name);
            formData.append("description", form.value.description || "");
            formData.append("duration", form.value.duration || "");
            formData.append("operation_days", form.value.operation_days || "");
            //Necessario verificar o null e undefined neste caso porque o formData tende a enviar como string em vez de null e entra em conflito no back end
            appendIfDefined("location_id", form.value.location_id);
            appendIfDefined("category_id", form.value.category_id);

            // Vou buscar todos os ids que se encontram nos subarrays divididos por ano.
            const allSelectedProviders =
                Object.values(providersCostsRef.value?.form?.providers_by_year || {}).flat() || [];
            const allSelectedClients = Object.values(clientsPricesRef.value?.form?.clients_by_year || {}).flat() || [];

            // Removo ids duplicados utilizando o Set().
            const uniqueProviderIds = [...new Set(allSelectedProviders)];
            const uniqueClientIds = [...new Set(allSelectedClients)];

            // Providers e Clients (array de ids) - para garantir que vai para o back-end como um array de ids no formDatapara garantir que vai para o back-end como um array de ids no formData
            uniqueProviderIds.forEach((providerId, index) => {
                formData.append(`providers[${index}]`, providerId);
            });
            uniqueClientIds.forEach((clientId, index) => {
                formData.append(`clients[${index}]`, clientId);
            });

            // ============ ANEXOS/FICHEIROS =========== //
            // Tratamento dos anexos
            let files = filesUploadRef.value.filesUploaded;

            // Novos ficheiros
            files.forEach((file, index) => {
                if (file.raw) {
                    formData.append(`new_attachments[${index}]`, file.raw); //Guardo o ficheiro em new_attachments, e nao _data, pois assim no backend é mais facil distinguir onde está o conteudo do ficheiro.
                    formData.append(`new_attachments_data[${index}][name]`, file.name);
                    formData.append(`new_attachments_data[${index}][size]`, file.size);
                    formData.append(`new_attachments_data[${index}][type]`, file.raw?.type);
                }
            });

            // Ficheiros existentes
            formData.append(
                "existing_attachments",
                JSON.stringify(
                    files
                        .filter((file) => file.url) // already uploaded
                        .map((file) => ({
                            name: file.name,
                            path: new URL(file.url).pathname, // extract just the path
                            size: file.size ?? null,
                            type: file.type ?? null,
                        })),
                ),
            );

            // Ficheiros eliminados, que ja se encontravam gravados no servidor
            const deletedPaths = filesUploadRef.value.filesDeleted;
            formData.append("deleted_attachments", JSON.stringify(deletedPaths));

            // ============ PREÇOS E CUSTOS DOS CLIENTES E FORNECEDORES =========== //
            // Obtenho os valores dos preços dos clientes do devido componente filho
            const new_prices_client = clientsPricesRef.value?.new_prices || [];
            const updated_prices_client = clientsPricesRef.value?.updated_prices || [];
            const deleted_prices_client = clientsPricesRef.value?.deleted_prices || [];

            // Obtenho os valores dos custos dos fornecedores do devido componente filho
            const new_costs_provider = providersCostsRef.value?.new_costs || [];
            const updated_costs_provider = providersCostsRef.value?.updated_costs || [];
            const deleted_costs_provider = providersCostsRef.value?.deleted_costs || [];

            // === FORNECEDOR === //
            // Custos associados aos fornecedores.
            // Novos custos associados ao fornecedor
            new_costs_provider.forEach((cost, index) => {
                formData.append(`new_costs[${index}][provider_id]`, cost.provider_id);
                formData.append(`new_costs[${index}][price]`, cost.price);
                formData.append(`new_costs[${index}][year]`, cost.year);
            });

            // Custos atualizados que ja existem na BD associados ao fornecedor
            updated_costs_provider.forEach((cost, index) => {
                formData.append(`updated_costs[${index}][provider_id]`, cost.provider_id);
                formData.append(`updated_costs[${index}][price]`, cost.price);
                formData.append(`updated_costs[${index}][year]`, cost.year);
            });

            // Custos removidos que ja existiam na BD associados ao fornecedor
            deleted_costs_provider.forEach((cost, index) => {
                formData.append(`deleted_costs[${index}][provider_id]`, cost.provider_id);
                formData.append(`deleted_costs[${index}][price]`, cost.price);
                formData.append(`deleted_costs[${index}][year]`, cost.year);
            });

            // === CLIENTE === //
            // Custos associados aos clientees.
            // Novos custos associados ao cliente
            new_prices_client.forEach((cost, index) => {
                formData.append(`new_prices_client[${index}][client_id]`, cost.client_id);
                formData.append(`new_prices_client[${index}][price]`, cost.price);
                formData.append(`new_prices_client[${index}][year]`, cost.year);
            });

            // Custos atualizados que ja existem na BD associados ao cliente
            updated_prices_client.forEach((cost, index) => {
                formData.append(`updated_prices_client[${index}][client_id]`, cost.client_id);
                formData.append(`updated_prices_client[${index}][price]`, cost.price);
                formData.append(`updated_prices_client[${index}][year]`, cost.year);
            });

            // Custos removidos que ja existiam na BD associados ao cliente
            deleted_prices_client.forEach((cost, index) => {
                formData.append(`deleted_prices_client[${index}][client_id]`, cost.client_id);
                formData.append(`deleted_prices_client[${index}][price]`, cost.price);
                formData.append(`deleted_prices_client[${index}][year]`, cost.year);
            });

            // ============ API REQUEST COM OS DADOS  =========== //
            // Por causa de usarmos o formData temos que especificamente utilizar o metodo POST.
            // No caso de ser PUT temos que especificar o metodo dentro do formData.
            if (props.editMode) {
                formData.append("_method", "PUT");
            }

            // Chamamos o metodo da API.
            const url = props.editMode
                ? `/api/products/${props.row.id}` // URL para editar um produto
                : "/api/products"; // URL para criar um produto

            // Pedido API
            const response = await axios.post(url, formData);

            if (response.data.success) {
                ElNotification({
                    title: "Success",
                    type: "success",
                    duration: 1400,
                });
                emit("update");
                closeModal();
                isLoading.value = false;
            } else {
                isLoading.value = false;
            }
        }
    } catch (error) {
        console.log(error);
        ElNotification({
            title: `Error - ${error.response.data.message}`,
            type: "error",
            duration: 1400,
        });
        isLoading.value = false;
    }
};

const resetForm = () => {
    if (!props.editMode) {
        form.value.id = "";
    }
    form.value.name = "";
    form.value.description = "";
    form.value.duration = "";
    form.value.operation_days = "";
    form.value.category_id = null;
    form.value.location_id = null;
    form.value.attachments = [];
    isLoading.value = false;
    selectedTab.value = "general";
};
</script>

<style scoped>
/* CSS HEADER DA TABELA */
::v-deep(.table-container-providers .el-table__header th) {
    background-color: #596c92 !important;
    color: white !important;
    font-size: 14px;
}

.cursor-pointer:hover {
    text-decoration: underline;
}

/* Trata o espacamento entre o el select e o titulo dos providers */
::v-deep(.pricesDropdown.el-form-item--label-top) {
    margin-top: 10px !important;
}

/* Define a cor do  background do input dos novos custos do provider */
::v-deep(.custom-input .el-input__wrapper),
::v-deep(.custom-input .el-input__inner) {
    background-color: white !important;
}

/* Alinha o texto do input do novos custos do provider a direita */
::v-deep(.custom-input .el-input__inner) {
    text-align: right;
}

/* Alinha os botões/tabs */
.selection-tabs-wrapper {
    display: flex;
    justify-content: flex-start;
    width: 100%;
    padding-left: 1rem;
    box-sizing: border-box;
    margin-bottom: 0.5rem;
}

/* Define a gap entre os botões */
.selection-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 1px;
}

/* Style default dos botões/tabs de seleção */
.tab {
    flex: 0 0 140px;
    text-align: center;
    border: 1px solid #ccc;
    background-color: white;
    color: #000;
    font-weight: 500;
    padding: 8px 0;
    font-size: 13px;
    transition:
        background-color 0.2s ease,
        color 0.2s ease;
    border-radius: 0;
}

/* Style aplicado as tabs quando estão ativas */
.tab.active {
    background-color: #17162b;
    color: white;
}
</style>
