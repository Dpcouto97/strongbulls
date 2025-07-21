<template>
    <AppLayout title="Plan">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-8">
                <div class="main-content">
                    <!-- Container com ICON + TITULO-->
                    <div class="title-container flex items-center space-x-2">
                        <span class="material-symbols-outlined" style="color: #1d3a32">flowsheet</span>
                        <h1 class="title" style="color: #1d3a32">{{ $t("plan") }}</h1>
                    </div>
                    <div class="filter-controls mb-3">
                        <div class="left-filter-container">
                            <span class="material-symbols-outlined" style="font-size: 25px">manage_search</span>
                            <div class="filter-controls flex-1">
                                <!-- SEARCH BAR -->
                                <el-input
                                    style="width: 280px"
                                    :placeholder="$t('search')"
                                    v-model="searchFilter"
                                    search
                                    clearable
                                    @change="getTableData"
                                    class="white-bg-input"
                                />
                                <!-- Client Filter -->
                                <el-select
                                    v-model="clientFilter"
                                    :placeholder="$t('clients')"
                                    style="width: 240px"
                                    multiple
                                    :collapse-tags="clientFilter.length > 1"
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

                    <!-- Modal de criacao/Edicao de Plano -->
                    <plan-modal
                        ref="plan"
                        v-model:visible="showModal"
                        :edit-mode="editMode"
                        :row="rowData"
                        :clients-list="clientsList"
                        @update="getTableData"
                    />

                    <!-- Modal Detalhes do Plano -->
                    <plan-details-modal ref="planDetails" v-model:visible="showDetailsModal" :data="rowData" :clients-list="clientsList" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PlanModal from "@/Components/Modals/Plan/planModal.vue";
import PlanDetailsModal from "@/Components/Modals/Plan/planDetailsModal.vue";
import axios from "axios";
import { ElNotification } from "element-plus";
import { ElMessageBox } from "element-plus";
import "../../../css/table.css";
import "../../../css/notification.css";
import AppLayout from "@/Layouts/AppLayout.vue";

//Define o nome dado ao ficheiro
defineOptions({
    name: "Plan",
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
const searchFilter = ref([]);
const clientsList = ref([]);
const pageSize = ref(10);
const currentPage = ref(1);
const totalItems = ref(0);
const can_create = ref(false); //Permissao Add
const sortColumn = ref(null);
const sortOrder = ref(null);

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const tableColumns = [
    {
        label: $t('name'),
        property: "name",
        icon: "flowsheet",
        sortable: true,
    },
    {
        label: $t('type'),
        property: "type",
        icon: "merge_type",
    },
];
// Metodos
const getTableData = async () => {
    //Busca a lista de Items da tabela da BD.
    let filters = {
        clientFilter: clientFilter.value,
        searchFilter: searchFilter.value,
        page: currentPage.value,
        pageSize: pageSize.value,
        sortBy: sortColumn.value,
        sortOrder: sortOrder.value,
    };

    try {
        isLoading.value = true;
        //Chamada GET API
        const response = await axios.get("/api/plans", { params: filters });

        if (response.data.success) {
            tableData.value = response.data.data.list.data;
            totalItems.value = response.data.data.list.total;
            can_create.value = response.data.data.permissions.can_create;
        } else {
        }
    } catch (error) {
        ElNotification({
            title: `Error - ${error.response.data.message}`,
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
        await ElMessageBox.confirm($t("confirm_delete_plan"), {
            confirmButtonText: $t("confirm"),
            cancelButtonText: $t("cancel"),
            type: "warning",
            center: true,
            buttonSize: "large",
            customClass: "custom-confirm-box",
        });

        // Chamo o metodo para eliminar o item
        const response = await axios.delete(`/api/plans/${id}`);

        if (response.data.success) {
            // Atualizo a tabela apos eliminar o item desejado
            getTableData();

            //Mostro msg de sucesso da eliminacao
            ElNotification({
                title: $t('success'),
                message: $t("success_deleting_plan"),
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
</style>
