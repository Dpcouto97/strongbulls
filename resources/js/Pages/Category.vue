<template>
    <AppLayout title="Category">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-8">
                <div class="main-content">
                    <!-- Container com ICON + TITULO-->
                    <div class="title-container flex items-center space-x-2">
                        <img :src="carIcon" alt="category" class="w-10 h-10" />
                        <h1 class="title">Categories List</h1>
                    </div>
                    <div class="filter-controls mb-4">
                        <div class="left-filter-container">
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
                        <el-table :data="tableData" style="width: 100%" max-height="auto" v-loading="isLoading">
                            <el-table-column
                                v-for="col in tableColumns"
                                :key="col.property"
                                :prop="col.property"
                                :min-width="col.minWidth"
                                :align="col.align"
                                :formatter="col.formatter"
                                class-name="left-gap"
                            >
                                <template #header>
                                    <div class="flex items-center gap-2">
                                        <img :src="col.icon" alt="icon" class="w-5 h-5 filter brightness-0 invert" />
                                        <span class="leading-none text-sm">{{ col.label }}</span>
                                    </div>
                                </template>
                            </el-table-column>
                            <!-- Colunas para botoes de acao ( add/edit/delete) -->
                            <el-table-column align="center" width="140" class-name="left-gap">
                                <template #header>
                                    <div v-if="can_create" class="flex items-center justify-left">
                                        <button
                                            class="icon-button add-button ml-4"
                                            @click="addItem"
                                            title="Add new Category"
                                        >
                                            <span class="material-symbols-outlined">add_box</span><span>Add</span>
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

                    <!-- Modal de criacao/Edicao de Categoria -->
                    <category-modal
                        ref="category"
                        v-model:visible="showModal"
                        :edit-mode="editMode"
                        :row="rowData"
                        @update="getTableData"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import CategoryModal from "@/Components/Modals/Category/categoryModal.vue";
import axios from "axios";
import { ElNotification } from "element-plus";
import { ElMessageBox } from "element-plus";
import { usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import "../../css/table.css";
import "../../css/notification.css";
import carIcon from "@/Icons/car.svg?url";
import userIcon from "@/Icons/user.svg?url";

//Defne o nome dado ao ficheiro
defineOptions({
    name: "Category",
});

//LifeCycle OnMounted
onMounted(() => {
    getTableData();
});

// Variaveis Reactivas - Acedidas em JS atraves de 'variavel.value'
const showModal = ref(false);
const editMode = ref(false);
const rowData = ref(null);
const isLoading = ref(false);
const tableData = ref([]);
const searchFilter = ref(null);
const pageSize = ref(10);
const currentPage = ref(1);
const totalItems = ref(0);
const can_create = ref(false); //Permissao Add

// Acesso a toda a informacao da pagina, especialmente o utilizador logado
const page = usePage();

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const tableColumns = [{ label: "Name", property: "name", icon: userIcon }];
// Metodos
const getTableData = async () => {
    //Busca a lista de Items da tabela da BD.
    try {
        isLoading.value = true;
        //Chamada GET API
        const response = await axios.get("/api/categories", {
            params: {
                searchFilter: searchFilter.value,
                page: currentPage.value,
                pageSize: pageSize.value,
            },
        });

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

const deleteItem = async (id) => {
    try {
        // Mostramos caixa de confirmacao para nao eliminar de forma inesperada.
        await ElMessageBox.confirm("Are you sure you want to delete this category?", {
            confirmButtonText: "Confirm",
            cancelButtonText: "Cancel",
            type: "warning",
            center: true,
            buttonSize: "large",
            customClass: "custom-confirm-box",
        });

        // Chamo o metodo para eliminar o item
        const response = await axios.delete(`/api/categories/${id}`);

        if (response.data.success) {
            // Atualizo a tabela apos eliminar o item desejado
            getTableData();

            //Mostro msg de sucesso da eliminacao
            ElNotification({
                title: "Success",
                message: "Category deleted successfully",
                type: "success",
                duration: 1400,
            });
        } else {
            console.log("Error deleting category");
        }
    } catch (error) {
        if (error === "cancel" || error === "close") {
            return;
        }
        console.error("Error", error);
        ElNotification({
            title: "Error",
            message: error.response?.data?.message || "Failed to delete the Category.",
            type: "error",
            duration: 2000,
        });
    }
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
}

.left-filter-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.title-container {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    width: calc(100% - 1px);
}

.title-container .material-symbols-outlined {
    font-size: 45px;
    line-height: 1;
}
</style>
