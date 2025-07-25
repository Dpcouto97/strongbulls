<template>
    <AppLayout title="Evaluation">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-8">
                <div class="main-content">
                    <!-- Container with ICON + TITLE on the left and BUTTON on the right -->
                    <div class="flex items-center justify-between">
                        <!-- Left Side: Icon + Title -->
                        <div class="title-container flex items-center space-x-2">
                            <span class="material-symbols-outlined" style="color: #1d3a32">health_metrics</span>
                            <h1 class="title" style="color: #1d3a32">{{ $t("evaluations") }}</h1>
                        </div>

                        <!-- Right Side: Chart/Table Button MODE -->
                        <button
                            v-if="visualizationMode === 'table'"
                            class="graphic-button"
                            @click="changeVisualizationMode('graphic')"
                            :title="$t('graphic_mode')"
                        >
                            <span class="material-symbols-outlined text-xl">timeline</span>
                        </button>

                        <button
                            v-else
                            class="graphic-button"
                            @click="changeVisualizationMode('table')"
                            :title="$t('table_mode')"
                        >
                            <span class="material-symbols-outlined text-xl">table</span>
                        </button>
                    </div>
                    <div class="filter-controls mb-3">
                        <div class="left-filter-container">
                            <span class="material-symbols-outlined" style="font-size: 25px">manage_search</span>
                            <div class="filter-controls flex-1">
                                <!-- DateTime Range Filter -->
                                <el-date-picker
                                    v-model="dateFilter"
                                    type="datetimerange"
                                    unlink-panels
                                    range-separator="-"
                                    :start-placeholder="$t('start')"
                                    :end-placeholder="$t('end')"
                                    :shortcuts="shortcuts"
                                    size="default"
                                    format="DD-MM-YYYY HH:mm"
                                    value-format="YYYY-MM-DD HH:mm:ss"
                                    @change="getTableData"
                                />

                                <!-- Client Filter -->
                                <el-select
                                    v-model="clientFilter"
                                    :placeholder="$t('choose_client')"
                                    style="width: 240px"
                                    clearable
                                    filterable
                                    @change="getTableData"
                                >
                                    <el-option
                                        v-for="item in clientsList"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id"
                                    />
                                </el-select>
                            </div>
                        </div>
                    </div>
                    <div v-if="visualizationMode === 'table'" class="table-mode-box">
                        <!-- Container TABELA/LISTA -->
                        <div class="table-container">
                            <el-table
                                :data="tableData"
                                style="width: 100%"
                                max-height="50vh"
                                @sort-change="handleSortChange"
                                v-loading="isLoading"
                            >
                                <el-table-column
                                    v-for="col in tableColumns"
                                    :key="col.property"
                                    :prop="col.property"
                                    :min-width="col.minWidth"
                                    :align="col.align"
                                    :formatter="col.formatter"
                                    :sortable="col.sortable"
                                >
                                    <template #header="{ column }">
                                        <span class="flex items-center gap-2">
                                            <span class="material-symbols-outlined">{{ col.icon }}</span>
                                            <span class="leading-none text-sm">{{ col.label }}</span>
                                        </span>
                                    </template>
                                </el-table-column>
                                <!-- Colunas para botoes de acao ( add/edit/delete) -->
                                <el-table-column align="right" width="140">
                                    <template #header>
                                        <div v-if="can_create" class="flex items-center justify-left">
                                            <button
                                                class="icon-button add-button ml-4"
                                                @click="addItem"
                                                :title="$t('add_new')"
                                            >
                                                <span class="material-symbols-outlined">add_box</span>
                                            </button>
                                        </div>
                                    </template>
                                    <template #default="{ row }">
                                        <div class="row-actions">
                                            <button
                                                v-if="row.can_edit"
                                                class="icon-button edit-button"
                                                @click="editItem(row)"
                                                :title="$t('edit')"
                                            >
                                                <span class="material-symbols-outlined">edit_square</span>
                                            </button>
                                            <button
                                                v-if="row.can_delete"
                                                class="icon-button delete-button"
                                                @click="deleteItem(row.id)"
                                                :title="$t('delete')"
                                            >
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                            <button
                                                v-if="row.can_details"
                                                class="icon-button details-button"
                                                @click="detailItem(row)"
                                                :title="$t('details')"
                                            >
                                                <span class="material-symbols-outlined">info</span>
                                            </button>
                                        </div>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </div>

                        <!-- PAGINACAO -->
                        <div class="pagination mt-8" style="display: flex; justify-content: start; height: 40px">
                            <el-pagination
                                v-model:current-page="currentPage"
                                :page-sizes="[5, 10, 20, 30, 50]"
                                :page-size="pageSize"
                                layout="prev, pager, next, total, sizes"
                                :total="totalItems"
                                background
                                @current-change="getTableData"
                                @size-change="onRemoteOperation"
                            />
                        </div>
                    </div>
                    <div v-else class="chart-mode-box">
                        <!-- Componente para mostrar avaliações em modo gráfico -->
                        <health-chart ref="chart" :evaluations="tableData" @update="getTableData" />
                    </div>

                    <!-- Modal de criacao/Edicao de Produto -->
                    <evaluation-modal
                        ref="evaluation"
                        v-model:visible="showModal"
                        :edit-mode="editMode"
                        :row="rowData"
                        :clients-list="clientsList"
                        @update="getTableData"
                    />

                    <!-- Modal Detalhes da Avaliação -->
                    <evaluation-details-modal
                        ref="evaluationDetails"
                        v-model:visible="showDetailsModal"
                        :data="rowData"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import EvaluationModal from "@/Components/Modals/Evaluation/evaluationModal.vue";
import EvaluationDetailsModal from "@/Components/Modals/Evaluation/evaluationDetailsModal.vue";
import healthChart from "@/Components/Custom/healthChart.vue";
import axios from "axios";
import { ElNotification } from "element-plus";
import { ElMessageBox } from "element-plus";
import "../../../css/table.css";
import "../../../css/notification.css";
import AppLayout from "@/Layouts/AppLayout.vue";
import moment from "moment";

//Defne o nome dado ao ficheiro
defineOptions({
    name: "Evaluation",
});

//LifeCycle OnMounted
onMounted(() => {
    getTableData();
    getClientsList();
});

// Variaveis Reactivas - Acedidas em JS atraves de 'variavel.value'
const $t = (key) => window.translations?.[key] || key;
const showModal = ref(false);
const showDetailsModal = ref(false);
const editMode = ref(false);
const rowData = ref(null);
const isLoading = ref(false);
const tableData = ref([]);
const clientFilter = ref([]);
const dateFilter = ref([]);
const clientsList = ref([]);
const pageSize = ref(10);
const currentPage = ref(1);
const totalItems = ref(0);
const can_create = ref(false); //Permissao Add
const sortColumn = ref(null);
const sortOrder = ref(null);
const visualizationMode = ref("table");

const shortcuts = [
    {
        text: $t("today"),
        value: () => {
            const today = new Date();
            const start = new Date(today.setHours(0, 0, 0, 0));
            const end = new Date();
            return [start, end];
        },
    },
    {
        text: $t("last_week"),
        value: () => {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 7);
            return [start, end];
        },
    },
    {
        text: $t("last_month"),
        value: () => {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 30);
            return [start, end];
        },
    },
    {
        text: $t("current_month"),
        value: () => {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            const end = new Date();
            return [start, end];
        },
    },
    {
        text: $t("last_three_months"),
        value: () => {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 90);
            return [start, end];
        },
    },
    {
        text: $t("last_year"),
        value: () => {
            const end = new Date();
            const start = new Date();
            start.setFullYear(end.getFullYear() - 1);
            return [start, end];
        },
    },
];

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const tableColumns = [
    {
        label: $t("date"),
        property: "date",
        formatter: (row) => moment(row.date).format("DD-MM-YYYY HH:mm"),
        icon: "calendar_today",
        sortable: true,
    },
    {
        label: $t("client"),
        property: "client_id",
        formatter: (row) => (row.client_id && row.client ? row.client.name : ""),
        icon: "person",
    },
    {
        label: $t("weight"),
        property: "weight",
        formatter: (row) => (row.weight ? row.weight + " kg" : " "),
        icon: "scale",
    },
    {
        label: "IMC",
        property: "imc",
        icon: "straighten",
        minWidth: 60
    },
    {
        label: $t("body_fat"),
        property: "body_fat",
        formatter: (row) => (row.body_fat ? row.body_fat + " %" : " "),
        icon: "body_fat",
    },
    {
        label: $t("muscle_mass"),
        property: "muscle_mass",
        formatter: (row) => (row.muscle_mass ? row.muscle_mass + " kg" : " "),
        icon: "exercise",
    },
];
// Metodos
const getTableData = async () => {
    //Busca a lista de Items da tabela da BD.
    let filters = {
        clientFilter: clientFilter.value,
        dateFilter: dateFilter.value,
        sortBy: sortColumn.value,
        sortOrder: sortOrder.value,
    };

    // Only add pagination if not in graphic mode
    const isTableMode = visualizationMode.value === "table";

    if (isTableMode) {
        filters.page = currentPage.value;
        filters.pageSize = pageSize.value;
    }

    try {
        isLoading.value = true;

        const response = await axios.get("/api/evaluations", { params: filters });

        if (response.data.success) {
            const list = response.data.data.list;

            // ✅ Handle table mode (with pagination)
            if (isTableMode) {
                tableData.value = list.data;
                totalItems.value = list.total;
            }
            // ✅ Handle graphic mode (no pagination)
            else {
                tableData.value = list;
            }

            can_create.value = response.data.data.permissions.can_create;
        }
    } catch (error) {
        ElNotification({
            title: `Error - ${error.response?.data?.message || "Unknown error"}`,
            type: "error",
            duration: 1400,
        });
    } finally {
        isLoading.value = false;
    }
};

const getClientsList = async () => {
    //Busca a lista de Clients da tabela da BD.
    try {
        const response = await axios.get("/api/clients", {
            params: {
                byPassPermission: true,
            },
        });

        if (response.data.success) {
            clientsList.value = response.data.data.list;
        }
    } catch (error) {
        ElNotification({
            title: `Error - ${error.response.data.message}`,
            type: "error",
            duration: 1400,
        });
    }
};

const addItem = () => {
    //Abro o modal modo Criação
    showModal.value = true;
    editMode.value = false;
};

const editItem = (row) => {
    // Abro o modal modo Edição
    showModal.value = true;
    editMode.value = true;
    rowData.value = row;
};

const detailItem = (row) => {
    // Abro o modal de Detalhes
    showDetailsModal.value = true;
    rowData.value = row;
};

const deleteItem = async (id) => {
    try {
        // Mostramos caixa de confirmacao para nao eliminar de forma inesperada.
        await ElMessageBox.confirm($t("confirm_delete_evaluation"), {
            confirmButtonText: $t("confirm"),
            cancelButtonText: $t("cancel"),
            type: "warning",
            center: true,
            buttonSize: "large",
            customClass: "custom-confirm-box",
        });

        // Chamo o metodo para eliminar o item
        const response = await axios.delete(`/api/evaluations/${id}`);

        if (response.data.success) {
            // Atualizo a tabela apos eliminar o item desejado
            getTableData();

            //Mostro msg de sucesso da eliminacao
            ElNotification({
                title: $t('success'),
                message: $t("success_deleting_evaluation"),
                type: "success",
                duration: 1400,
            });
        }
    } catch (error) {
        if (error === "cancel" || error === "close") {
            return;
        }
        console.error("Error", error);
        ElNotification({
            title: "Error",
            message: error.response?.data?.message || $t("error_deleting_evaluation"),
            type: "error",
            duration: 2000,
        });
    }
};

const changeVisualizationMode = (mode) => {
    visualizationMode.value = mode;

    if (mode === "graphic") {
        sortColumn.value = "date";
        sortOrder.value = "asc";
    }
    getTableData();
};

const handleSortChange = ({ column, prop, order }) => {
    // Funcao que trata de ordenar por coluna e asc ou desc
    sortColumn.value = prop;
    sortOrder.value = order === "ascending" ? "asc" : order === "descending" ? "desc" : null;
    getTableData();
};

const onRemoteOperation = (val) => {
    // Atualizo o pageSize e CurrentPage sempre que o valor é alterado
    pageSize.value = val;
    currentPage.value = 1;
    getTableData();
};
</script>

<style scoped>
.title {
    font-weight: 500;
    font-size: 40px;
    padding: 10px;
    color: #a48a62;
    text-align: left;
    font-family: "Georgia", "Times New Roman", serif;
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: space-between;
    margin-left: 4px;
}

.left-filter-container {
    display: flex;
    align-items: center;
    gap: 0.2rem;
}

.title-container {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.title-container .material-symbols-outlined {
    font-size: 45px;
    line-height: 1; /* optional for tighter alignment */
}

.truncated-cell {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

/* CSS PARA O BOTAO DE MODO GRAFICO */
.graphic-button {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid black;
    background-color: #1d3a32;
    border-radius: 15%;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.graphic-button:hover {
    background-color: #fdedc8;
}

.graphic-button .material-symbols-outlined {
    font-size: 24px;
    color: #ffd400;
}

.graphic-button:hover .material-symbols-outlined {
    color: #1d3a32;
}
</style>
