<template>
    <AppLayout title="Client">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-8">
                <div class="main-content">
                    <!-- Container com ICON + TITULO-->
                    <div class="title-container flex items-center space-x-2">
                        <span class="material-symbols-outlined" style="color: #1d3a32">contacts_product</span>
                        <h1 class="title" style="color: #1d3a32">{{ $t('client') }}</h1>
                    </div>
                    <div class="filter-controls mb-4">
                        <div class="right-filter-container">
                            <div><span class="material-symbols-outlined" style="font-size: 25px">search</span></div>
                            <el-input
                                style="width: 280px"
                                placeholder="Search..."
                                v-model="searchFilter"
                                search
                                clearable
                                @change="getTableData"
                                class="white-bg-input"
                            />
                        </div>
                    </div>
                    <!-- Container TABELA/LISTA -->
                    <div class="table-container">
                        <el-table :data="tableData" style="width: 100%" max-height="320" v-loading="isLoading" @sort-change="handleSortChange">
                            <el-table-column
                                v-for="col in tableColumns"
                                :key="col.property"
                                :prop="col.property"
                                :min-width="col.minWidth"
                                :align="col.align"
                                :formatter="col.formatter"
                                :sortable="col.sortable"
                            >
                                <template #header>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined">{{ col.icon }}</span>
                                        <span class="leading-none text-sm">{{ col.label }}</span>
                                    </div>
                                </template>
                            </el-table-column>
                            <!-- Colunas para botoes de acao ( add/edit/delete) -->
                            <el-table-column align="center" width="140" class-name="left-gap">
                                <template #header>
                                    <div v-if="can_create" class="flex items-center justify-center">
                                        <button class="icon-button add-button" @click="addItem" title="Add new Client">
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
                                            title="Edit"
                                        >
                                            <span class="material-symbols-outlined">edit_square</span>
                                        </button>
                                        <button
                                            v-if="row.can_delete"
                                            class="icon-button delete-button"
                                            @click="deleteItem(row.id)"
                                            title="Delete"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                        <button
                                            v-if="row.can_details"
                                            class="icon-button details-button"
                                            @click="detailItem(row)"
                                            title="Details"
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
                            size="small"
                            layout="prev, pager, next, total, sizes"
                            :total="totalItems"
                            background
                            @current-change="getTableData"
                            @size-change="onRemoteOperation"
                        />
                    </div>

                    <!-- Modal de criacao/Edicao -->
                    <client-modal
                        ref="provider"
                        v-model:visible="showModal"
                        :edit-mode="editMode"
                        :row="rowData"
                        @update="getTableData"
                    />

                    <!-- Modal Detalhes do Cliente -->
                    <client-details-modal ref="providerDetails" v-model:visible="showDetailsModal" :data="rowData" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import ClientModal from "@/Components/Modals/Client/clientModal.vue";
import ClientDetailsModal from "@/Components/Modals/Client/clientDetailsModal.vue";
import axios from "axios";
import { ElNotification } from "element-plus";
import { ElMessageBox } from "element-plus";
import { usePage } from "@inertiajs/vue3";
import "../../../css/table.css";
import "../../../css/notification.css";
import AppLayout from "@/Layouts/AppLayout.vue";
import clientIcon from "@/Icons/client.svg?url";
import clientWhiteIcon from "@/Icons/clientWhiteIcon.svg?url";

//Defne o nome dado ao ficheiro
defineOptions({
    name: "Client",
});

//LifeCycle OnMounted
onMounted(() => {
    getTableData();
});

// Variaveis Reactivas - Acedidas em JS atraves de 'variavel.value'
const showModal = ref(false);
const showDetailsModal = ref(false);
const editMode = ref(false);
const rowData = ref(null);
const isLoading = ref(false);
const tableData = ref([]);
const searchFilter = ref(null);
const pageSize = ref(10);
const currentPage = ref(1);
const totalItems = ref(0);
const can_create = ref(false); //Permissao Add
const sortColumn = ref(null);
const sortOrder = ref(null);

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const tableColumns = [{ label: "Name", property: "name", minWidth: 350, icon: "person",sortable: true, },
    { label: "Phone", property: "phone_number", minWidth: 100, icon: "call" }];

// Acesso a toda a informacao da pagina, especialmente o utilizador logado
const pageInfo = usePage();

// Metodos
const getTableData = async () => {
    //Busca a lista de Items da tabela da BD.
    let filters = {
        searchFilter: searchFilter.value,
        page: currentPage.value,
        pageSize: pageSize.value,
        sortBy: sortColumn.value,
        sortOrder: sortOrder.value,
    };

    try {
        isLoading.value = true;
        //Chamada GET API
        const response = await axios.get("/api/clients", { params: filters });

        if (response.data.success) {
            tableData.value = response.data.data.list.data;
            totalItems.value = response.data.data.list.total;
            can_create.value = response.data.data.permissions.can_create;
            isLoading.value = false;
        } else {
            isLoading.value = false;
        }
    } catch (error) {
        ElNotification({
            title: `Error - ${error.response.data.message}`,
            type: "error",
            duration: 1400,
        });
        isLoading.value = false;
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
        await ElMessageBox.confirm("Are you sure you want to delete this client?", {
            confirmButtonText: "Confirm",
            cancelButtonText: "Cancel",
            type: "warning",
            center: true,
            buttonSize: "large",
            customClass: "custom-confirm-box",
        });

        // Chamo o metodo para eliminar o item
        const response = await axios.delete(`/api/clients/${id}`);

        if (response.data.success) {
            // Atualizo a tabela apos eliminar o item desejado
            getTableData();

            //Mostro msg de sucesso da eliminacao
            ElNotification({
                title: "Success",
                message: "Client deleted successfully",
                type: "success",
                duration: 1400,
            });
        } else {
            console.log("Error deleting provider");
        }
    } catch (error) {
        if (error === "cancel" || error === "close") {
            return;
        }
        console.error("Error", error);
        ElNotification({
            title: "Error",
            message: error.response?.data?.message || "Failed to delete the Client.",
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
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: space-between;
}

.right-filter-container {
    display: flex;
    align-items: center;
    gap: 5px;
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
</style>
