<template>
    <div class="files-upload-container">
        <el-upload
            ref="uploadRef"
            class="upload-image"
            v-model:file-list="filesList"
            action=""
            multiple
            :show-file-list="true"
            :on-remove="handleRemoveFile"
            :on-change="handleChangedFiles"
            :on-preview="handlePreview"
            :before-remove="beforeRemove"
            :auto-upload="false"
        >
            <div class="flex flex-row gap-2 items-center">
                <div class="upload-container">
                    <el-button>{{ $t('choose_files') }}</el-button>
                </div>
                <div v-if="filesList.length < 1">{{ $t('no_file_choosen') }}</div>
            </div>
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
    // Tamanho maximo por ficheiro em MB
    const maxSizeInMB = 10;

    // Removo ficheiros com mais 10MB
    const validFiles = uploadFiles.filter((f) => f.size / 1024 / 1024 <= maxSizeInMB);

    // Aviso o utilizador que alguns ficheiros ultrapassavam o tamanho maximo do ficheiro
    if (validFiles.length < uploadFiles.length) {
        ElNotification({
            title: "Some files were removed because they exceed the 10MB limit.",
            type: "warning",
            duration: 2300,
        });
    }

    // Atualizo as listagens
    filesUploaded.value = [...validFiles]; // Lista de ficheiros a enviar para a API
    filesList.value = [...validFiles]; // Lista default do el-upload
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
    return ElMessageBox.confirm(`Are you sure you want to delete<br><u>${uploadFile.name}</u>?`, {
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
