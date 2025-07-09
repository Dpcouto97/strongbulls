<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit User Group" : "Create User Group" }}
                </h2>
            </div>
        </template>
        <!-------- FORMULARIO ------->
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
            <div class="flex flex-wrap gap-4">
                <!-- Name-->
                <el-form-item label="Name" prop="name" class="flex-1 w-full">
                    <el-input v-model="form.name" placeholder="Write here..." />
                </el-form-item>

                <!-- STATUS -->
                <el-form-item label="Status" prop="status" class="flex-1 w-full">
                    <el-select v-model="form.status" placeholder="Choose Status">
                        <el-option
                            v-for="item in statusList"
                            :key="item.value"
                            :label="item.name"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
            </div>
        </el-form>
        <!-- TABELA DAS PERMISSOES -->
        <div class="table-container">
            <el-table :data="form.permissions" style="width: 100%" max-height="320">
                <el-table-column
                    v-for="col in tableColumns"
                    :key="col.property"
                    :prop="col.property"
                    :min-width="col.minWidth"
                    :align="col.align"
                    :formatter="col.formatter"
                >
                    <!-- Colunas (Icon + Texto)  -->
                    <template #header>
                        <div class="flex items-center gap-2">
                            <img
                                v-if="col.icon"
                                :src="col.icon"
                                alt="icon"
                                class="w-5 h-5 filter brightness-0 invert"
                            />
                            <span class="leading-none text-sm">{{ col.label }}</span>
                        </div>
                    </template>

                    <!-- Linhas (Modulo mostro valor, mas o resto sao checkboxs.)  -->
                    <template #default="{ row }">
                        <!-- Se for o modulo, mostro normalmente -->
                        <span v-if="col.property === 'module'" class="text-left block w-full">
                            {{ row.module.charAt(0).toUpperCase() + row.module.slice(1) }}
                        </span>

                        <!-- Todas as restantes são checkbox -->
                        <div v-else class="flex justify-center items-center h-full">
                            <el-checkbox v-model="row[col.property]" size="small" />
                        </div>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <!-- Botao GUARDAR O MODAL-->
        <template #footer>
            <div class="flex justify-end space-x-2">
                <!-- Reset Button -->
                <el-button v-loading="isLoading" size="default" @click="resetForm" class="text-white reset-item" :disabled="isLoading">RESET</el-button>
                <!-- Save Button -->
                <el-button v-loading="isLoading" type="primary" size="default" class="text-white save-item" @click="submitUpdate" :disabled="isLoading">
                    SAVE
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import { ElNotification } from "element-plus";
import axios from "axios";
import "../../../../css/form.css";
import "../../../../css/table.css";
import visibleIcon from "@/Icons/visible.svg?url";
import addIcon from "@/Icons/add.svg?url";
import editIcon from "@/Icons/edit.svg?url";
import deleteIcon from "@/Icons/delete.svg?url";
import infoIcon from "@/Icons/info.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "UserModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    row: Object,
    editMode: Boolean,
});

//Define os emits
const emit = defineEmits(["update:visible", "update"]);

// Ref - define uma variavel para uso geral no componente
// Variaveis Reativas
const formRef = ref(null);
const form = ref({
    id: "",
    name: "",
    status: true,
    permissions: [],
});
const isLoading = ref(false);

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const tableColumns = [
    { label: "Modules", property: "module", minWidth: 140 },
    {
        label: "List",
        property: "list",
        icon: visibleIcon,
    },
    {
        label: "Create",
        property: "create",
        icon: addIcon,
    },
    {
        label: "Edit",
        property: "edit",
        icon: editIcon,
    },
    {
        label: "Delete",
        property: "delete",
        icon: deleteIcon,
    },
    {
        label: "Details",
        property: "details",
        icon: infoIcon,
    },
];

// Lista base que alimenta a tabela das permissões, nao alteravel
const permissionsBaseList =[
    {
        module: "user",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    },
    {
        module: "client",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    },
    {
        module: "plan",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    },
    {
        module: "exercise",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    },
    {
        module: "appointment",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    },
    {
        module: "evaluation",
        list: false,
        create: false,
        edit: false,
        delete: false,
        details: false,
    }
];

const statusList = [
    {
        name: "Inactive",
        value: false,
    },
    {
        name: "Active",
        value: true,
    },
];

// Como as rules nao sao reactivas/nao sao alteradas entao nao precisamos de criar com 'ref'
const rules = {
    name: [
        { required: false, message: "Please input a Name", trigger: "blur" },
        {
            min: 1,
            max: 50,
            message: "Length should be 1 to 255",
            trigger: "blur",
        },
    ],
};

const openModal = () => {
    // Ao abrir o modal se estou em modo de edicao e tenho os dados preencho o formulario.
    if (props.editMode && props.row) {
        Object.assign(form.value, props.row);
        if (form.value.permissions.length < 1) {
            form.value.permissions = permissionsBaseList.map(item => ({ ...item }));
        }
        // Modulos existentes guardados na BD
        const existingModules = form.value.permissions.map((p) => p.module);

        // Deteto quais os modulos novos que podem estar em falta.
        const missingModules = permissionsBaseList.filter(
            (basePerm) => !existingModules.includes(basePerm.module)
        );

        // Se existir Modulos em falta entao adiciono as permissoes default.
        if(missingModules.length > 0){
            form.value.permissions.push(...missingModules.map((p) => ({ ...p })));
        }
    } else {
        // Caso seja um novo grupo, as permissoes sao a base que serao alteradas pelo user.
        // Clone do array de permissões base, para criar novas referencias.
        form.value.permissions = permissionsBaseList.map(item => ({ ...item }));
    }
};

const closeModal = () => {
    formRef.value?.clearValidate();
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
            const url = props.editMode
                ? `/api/groups/${props.row.id}` // URL para editar uma categoria
                : "/api/groups"; // URL para criar uma categoria

            const method = props.editMode ? "put" : "post"; // Usar PUT se editar, usar POST se criar.

            // Pedido API
            const response = await axios[method](url, { ...form.value });

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
    form.value.status = true;
    form.value.permissions = permissionsBaseList.map(item => ({ ...item }));
};
</script>

<style scoped>
::v-deep(.el-select__wrapper) {
    background-color: #f2f2f2 !important;
    border-radius: 4px;
    border: none !important;
    box-shadow: none !important;
}

.save-item {
    background-color: #596c92;
    border-radius: 0;
    border: 1px solid black;
    width: 100px;

    &:hover {
        background-color: #7a8fba;
    }
}

.reset-item {
    background-color: white;
    border-radius: 0;
    border: 1px solid black;
    width: 100px;

    &:hover {
        background-color: #e5e7eb;
        color: black;
    }
}

::v-deep(.el-checkbox__input) {
    width: 22px;
    height: 22px;
}

::v-deep(.el-checkbox__inner) {
    width: 100px;
    height: 100px;
}
::v-deep(.el-checkbox__inner::after) {
    border-width: 2px;
    left: 6px;
    top: 3px;
    width: 6px;
    height: 12px;
    border-width: 3px; /* Determina a grossura do check */
    border-color: white !important;
}

::v-deep(.el-checkbox.el-checkbox--small .el-checkbox__inner) {
    height:18px;
    width:18px;
    border:1px solid #9ca3af;
}

::v-deep(.el-checkbox__input.is-checked .el-checkbox__inner) {
    background-color: black;
}
</style>
