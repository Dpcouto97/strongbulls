<template>
    <div class="files-upload-container">
        <el-upload
            ref="uploadRef"
            class="upload-image"
            v-model:file-list="filesList"
            action=""
            :accept="singleFile ? 'image/jpeg,image/png,image/svg+xml' : ''"
            :limit="singleFile ? 1 : undefined"
            :multiple="!singleFile"
            :show-file-list="true"
            :on-remove="handleRemoveFile"
            :on-change="handleChangedFiles"
            :on-preview="handlePreview"
            :before-remove="beforeRemove"
            :auto-upload="false"
            :list-type="singleFile ? 'picture-card' : 'text'"
        >
            <!-- IF singleFile and no image is selected -->
            <template v-if="singleFile && filesList.length === 0">
                <div class="flex flex-col items-center justify-center cursor-pointer">
                    <el-icon><span class="material-symbols-outlined">upload</span></el-icon>
                    <div class="mt-1 text-sm text-gray-500">{{ $t("choose_file") }}</div>
                </div>
            </template>

            <!-- IF multiple files allowed -->
            <template v-else-if="!singleFile">
                <el-button>{{ $t("choose_files") }}</el-button>
            </template>
        </el-upload>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { ElMessageBox, ElNotification } from "element-plus";
import "../../../css/notification.css";

// Define o nome do ficheiro
defineOptions({
    name: "filesUpload",
});

const props = defineProps({
    singleFile: {
        type: Boolean,
        default: false,
    },
});

const $t = (key) => window.translations?.[key] || key;
const filesList = ref([]); // Lista que alimenta por default a preview list do el-upload
const filesUploaded = ref([]); // Lista de ficheiros adicionados
const filesDeleted = ref([]); // Lista de ficheiros removidos e que ja existiam no servidor
const uploadRef = ref(null); // Referencia do elemento el-upload

const setFiles = (files) => {
    // Define os valores dos arrays recebidos pelo componente pai (modal de criar/editar)
    filesList.value = [...files];
    filesUploaded.value = [...files];
    filesDeleted.value = [];
};

const handleChangedFiles = (file, uploadFiles) => {
    const maxSizeInMB = 10;
    const validMimeTypes = ["image/jpeg", "image/png", "image/svg+xml"];

    let validFiles = uploadFiles.filter((f) => {
        const isSizeOk = f.size / 1024 / 1024 <= maxSizeInMB;
        const isTypeOk = !props.singleFile || validMimeTypes.includes(f.raw?.type);
        return isSizeOk && isTypeOk;
    });

    if (props.singleFile && validFiles.length > 1) {
        // If singleFile is true, only keep the last one
        validFiles = [validFiles[validFiles.length - 1]];
    }

    if (validFiles.length < uploadFiles.length) {
        ElNotification({
            title: props.singleFile
                ? "Only JPEG, PNG or SVG images under 10MB are allowed."
                : "Some files were removed because they exceed the 10MB limit.",
            type: "warning",
            duration: 2300,
        });
    }

    filesUploaded.value = [...validFiles];
    filesList.value = [...validFiles];
};

const handleRemoveFile = (file, uploadFiles) => {
    // Logica para remocao dos ficheiros ja existentes no servidor.
    if (file.url) {
        // Se ja existia (possui URL) entao adiciono ao array de removidos
        filesDeleted.value.push(file);
    } else {
        // Se acabou de ser adicionado apenas atualizo a lista de ficheiros uplodaded.
        filesUploaded.value = [...uploadFiles];
    }
};

const beforeRemove = (uploadFile, uploadFiles) => {
    // Confirmacao acerca da remocao de um ficheiro, para evitar remoção não intencional.
    return ElMessageBox.confirm(`${$t("confirm_delete_attachment")}<br><u>${uploadFile.name}</u>?`, {
        confirmButtonText: "Confirm",
        cancelButtonText: "Cancel",
        center: true,
        buttonSize: "large",
        customClass: "custom-confirm-box",
        dangerouslyUseHTMLString: true,
    });
};

const handlePreview = (file) => {
    // Logica para fazer download do ficheiro
    const downloadFile = (url, filename) => {
        const link = document.createElement("a");
        link.href = url;
        link.download = filename || "download";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    if (file.raw) {
        // Ficheiro Local — Crio um  Blob URL e faco o download
        const blobUrl = URL.createObjectURL(file.raw);
        downloadFile(blobUrl, file.name);
        URL.revokeObjectURL(blobUrl); // Clean up
    } else if (file.url) {
        // Ficheiro do Servidor — Download direto.
        downloadFile(file.url, file.name);
    } else {
        ElMessage.warning("File unavailable.");
    }
};

const resetUpload = () => {
    uploadRef.value?.clearFiles(); // Limpo a lista 'interna' de ficheiros
    filesList.value = []; // Limpa a lista de ficheiros default
    filesUploaded.value = []; // Limpa a lista de ficheiros
    filesDeleted.value = []; // Limpa a lista de ficheiros eliminados
};

defineExpose({
    // Necessario para o componente pai conseguir aceder através da referencia.
    filesUploaded,
    filesDeleted,
    resetUpload,
    setFiles,
});
</script>

<style scoped>
.upload-image {
    width: 100%;
}

.files-upload-container {
    width: 100%;
}
</style>
