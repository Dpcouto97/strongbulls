<template>
    <el-form :model="form" ref="formRef" label-position="top" class="space-y-5">
        <!-- =============== CLIENT Form ================ -->
        <!-- Custom Label + Tabs (Fora do  el-form-item) -->
        <div class="flex items-center space-x-2 mb-1">
            <img :src="clientIcon" alt="icon" class="w-4 h-4" />
            <span class="form-label">Clients</span>
            <!-- Tabs -->
            <div v-if="editMode || form.client_prices.length > 0" class="flex items-center space-x-2 ml-4">
                <span
                    v-for="year in availableYears"
                    :key="year"
                    @click.stop="selectedYearTab = String(year)"
                    class="cursor-pointer text-sm"
                    :class="{
                        'text-black underline font-medium': selectedYearTab === String(year),
                        'text-gray-400': selectedYearTab !== String(year),
                    }"
                >
                    {{ year }}
                </span>
            </div>
        </div>
        <el-form-item prop="clients" class="pricesDropdown">
            <el-select
                v-model="form.clients_by_year[selectedYearTab]"
                multiple
                placeholder="Choose Clients"
                :collapse-tags="(form.clients_by_year[selectedYearTab] || []).length > 4"
                clearable
                filterable
                @change="handlePrices"
            >
                <el-option v-for="item in clientsList" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
        </el-form-item>

        <!-- Client NEW PRICES Table -->
        <el-form-item v-if="filteredNewPrices.length > 0">
            <div class="space-y-4 w-full">
                <div
                    v-for="(cost, index) in filteredNewPrices"
                    :key="cost.client_id + '_' + cost.year"
                    class="grid grid-cols-3 gap-4 items-center"
                >
                    <!-- Client Name -->
                    <span class="font-medium">
                        {{ getClientNameById(cost.client_id) }}
                    </span>

                    <!-- Price Input -->
                    <el-input
                        v-model.number="cost.price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="custom-input w-full border border-gray-300 text-right"
                    >
                        <template #prefix>
                            <span style="color: #333">Price</span>
                        </template>
                        <template #suffix>
                            <span style="color: #333">€</span>
                        </template>
                    </el-input>

                    <!-- Year Input -->
                    <el-input
                        v-model="cost.year"
                        type="number"
                        class="custom-input w-full border border-gray-300 text-right"
                        placeholder="Year"
                        :disabled="editMode"
                    >
                        <template #prefix><span style="color: #333">Year</span></template>
                    </el-input>
                </div>
            </div>
        </el-form-item>

        <!-- Client EXISTING PRICES TABLE -->
        <div v-if="filteredPrices.length > 0" class="table-container-providers">
            <el-table :data="filteredPrices" style="width: 100%">
                <el-table-column
                    v-for="col in pricesTableColumns"
                    :key="col.property"
                    :prop="col.property"
                    :min-width="col.minWidth"
                    :align="col.align"
                    :formatter="col.formatter"
                >
                    <!-- Colunas (Icon + Texto)  -->
                    <template #header>
                        <div class="flex items-center gap-2">
                            <img :src="col.icon" alt="icon" class="w-5 h-5 filter brightness-0 invert" />
                            <span class="leading-none text-sm">{{ col.label }}</span>
                        </div>
                    </template>

                    <!-- Linhas (Se for o preço mostro um input)  -->
                    <template #default="{ row }">
                        <!-- Se for o price, mostro como input -->
                        <span v-if="col.property === 'price'" class="text-left block w-full">
                            <el-input
                                v-model.number="row.price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Price"
                                class="w-full"
                                @change="handlePriceChange(row)"
                            />
                        </span>
                    </template>
                </el-table-column>
            </el-table>
        </div>
    </el-form>
</template>
<script setup>
import { ref, computed } from "vue";
import usePriceCalculations from "@/Composables/usePriceCalculations.js";
import "../../../../css/form.css";
import priceIcon from "@/Icons/euro.svg?url";
import clientIcon from "@/Icons/client.svg?url";
import vatIcon from "@/Icons/vat.svg?url";
import noVatIcon from "@/Icons/noVat.svg?url";

defineOptions({
    name: "ProductClientsPrices",
});

// Define as props
const props = defineProps({
    data: Object,
    editMode: Boolean,
    clientsList: Array,
});

// Variaveis Ref comuns ao componente
const formRef = ref(null);
const availableYears = ref([]); // Anos disponiveis segundo os existentes nos dados recebidos (dinamico) sobre o cliente
const selectedYearTab = ref(""); // tab do ano selecionado sobre o cliente
const new_prices = ref([]);
const updated_prices = ref([]);
const deleted_prices = ref([]);
const form = ref({
    id: "",
    clients: [],
    clients_by_year: {},
    client_prices: [],
});

// Colunas da tabela de preços existentes associada aos clientes e produto.
const pricesTableColumns = [
    {
        label: "Client Name",
        property: "client_id",
        formatter: (row) => (row.client_id && row.client ? row.client.name : ""),
        icon: clientIcon,
    },
    {
        label: "Price w/o VAT",
        property: "vat",
        formatter: (row) => getPriceWithoutVatFormat(row),
        icon: noVatIcon,
    },
    {
        label: "VAT",
        property: "vat",
        formatter: (row) => getVatValueFormat(row),
        icon: vatIcon,
    },
    {
        label: "Price",
        property: "price",
        icon: priceIcon,
    },
];
// Defino as funções a serem importadas do usePriceCalculations
const { getPriceWithoutVat, getVatValue } = usePriceCalculations();

// Devolve os preços existentes filtrados por ano
const filteredPrices = computed(() => {
    return form.value.client_prices.filter((cost) => cost.year === Number(selectedYearTab.value));
});

// Devolve os novos preços filtrados por ano
const filteredNewPrices = computed(() => {
    const year = Number(selectedYearTab.value);
    return props.editMode ? new_prices.value.filter((cost) => cost.year === year) : new_prices.value;
});

const manageYears = (costsArray, availableYearsRef, selectedYearRef) => {
    // Funcao que define os anos existentes e escolhe o mais recent para ficar selecionado
    const yearSet = new Set(costsArray.map((cost) => cost.year));
    // Senao existir nenhum ano entao fica o mais recente (ano atual)
    if (yearSet.size === 0) {
        yearSet.add(new Date().getFullYear());
    }

    const sortedYears = [...yearSet].sort((a, b) => b - a);
    availableYearsRef.value = sortedYears;
    selectedYearRef.value = String(sortedYears[0]); // seleciona o ano atual.
};

const scrollToTop = () => {
    // Garante que quando  entro na aba faz scroll para o topo
    const formEl = formRef.value?.$el;
    if (formEl) {
        if (typeof formEl.scrollTo === "function") {
            formEl.scrollTo({ top: 0, behavior: "instant" });
        } else {
            formEl.scrollTop = 0;
        }
    }
};

const loadData = () => {
    // Função chamada sempre que abro o modal do produto
    // Atualiza os dados com os do produto que estamos a editar, se estivermos em edição.
    if (props.editMode && props.data) {
        form.value = JSON.parse(JSON.stringify(props.data));
        scrollToTop();
        manageYears(form.value.client_prices, availableYears, selectedYearTab);
    }
};

const getClientNameById = (id) => {
    // Obtem o nome do client com base no id
    const client = props.clientsList.find((p) => p.id === id);
    return client ? client.name : `Client ${id}`;
};

const handlePrices = (selectedClients) => {
    // Função que trata de detetar novos preços, e preços existentes que foram removidos.
    const currentYear = Number(selectedYearTab.value); // Ano selecionado

    // Crio uma copia para prevenir alterar o array originial
    const currentClients = [...(selectedClients || [])];

    // Se existem custos associados ou estamos em modo de edicao.
    if (form.value.client_prices.length > 0 || props.editMode) {
        // ids dos clients ja guardados na BD
        const savedClientsInYear = form.value.client_prices
            .filter((cost) => cost.year === currentYear)
            .map((cost) => cost.client_id);

        // ids dos novos clientes selecionados
        const newClientsInYear = new_prices.value
            .filter((cost) => cost.year === currentYear)
            .map((cost) => cost.client_id);

        // Adiciono apenas novos clients (nao se encontra no array "new" nem nos "saved"-guardados na bd)
        currentClients.forEach((clientId) => {
            const isSaved = savedClientsInYear.includes(clientId);
            const isNew = newClientsInYear.includes(clientId);

            // Se não é novo nem existia na BD entao adiciono ao array de novos custos desse client-product
            if (!isSaved && !isNew) {
                new_prices.value.push({
                    client_id: clientId,
                    price: 0,
                    year: currentYear,
                });
            }
        });

        // Removo "Novos" clientes que tenham sido deselecionados.
        // Isso vai eliminar da lista `new_prices` qualquer item cujo cliente não esteja mais selecionado
        new_prices.value = new_prices.value.filter((cost) => {
            const sameYear = cost.year === currentYear;
            const stillSelected = currentClients.includes(cost.client_id);
            return !sameYear || stillSelected;
        });

        // Removo Clientes que ja estavam guardados na BD e que foram deselecionados
        // Removo da tabela de custos associados
        // Adiciono o custo associado removido ao array de eliminados (deleted_prices)
        form.value.client_prices = form.value.client_prices.filter((cost) => {
            const isSameYear = cost.year === currentYear;

            // Para outros anos, nao considero. Apenas ano atualmente selecionado
            if (!isSameYear) return true;

            const stillSelected = selectedClients.includes(cost.client_id);

            // Se o Cliente existente for removido movo para o array de eliminados
            if (!stillSelected) {
                deleted_prices.value.push(cost);
            }

            return stillSelected;
        });
    } else {
        const newClientsIds = new_prices.value.map((cost) => cost.client_id);
        // Adiciono novo custo apenas se o cliente ja nao estiver selecionado.
        currentClients.forEach((clientId) => {
            const isNew = newClientsIds.includes(clientId);
            if (!isNew) {
                new_prices.value.push({
                    client_id: clientId,
                    price: 0,
                    year: new Date().getFullYear(),
                });
            }
        });

        // Removo "Novos" clientes que tenham sido deselecionados.
        new_prices.value = new_prices.value.filter((cost) => currentClients.includes(cost.client_id));
    }
};

const handlePriceChange = (cost) => {
    // Função que trata de detetar alteração de preços existentes na BD
    // Verifico se o custo ja foi alterado anteriormente
    const exists = updated_prices.value.find((c) => c.client_id === cost.client_id && c.year === cost.year);

    // Senao foi alterado entao adicionamos a associacao com a alteracao ao array updated
    if (!exists) {
        updated_prices.value.push({
            client_id: cost.client_id,
            price: cost.price,
            year: cost.year,
        });
    } else {
        // Se ja foi alterado antes entao so atualizamos o preço.
        exists.price = cost.price;
    }
};

const getPriceWithoutVatFormat = (row) => {
    // Obtem o preço em euros sem VAT
    // Faço uma copia dos dados do client
    const obj = { ...row.client };

    return getPriceWithoutVat({
        ...obj,
        costData: { ...row },
    });
};

const getVatValueFormat = (row) => {
    // Obtem o valor de VAT em euros
    // Faço uma copia dos dados do client
    const obj = { ...row.client };

    return getVatValue({
        ...obj,
        costData: { ...row },
    });
};

const clearData = () => {
    // Limpa todos os dados.
    form.value.clients = [];
    form.value.clients_by_year = {};
    form.value.client_prices = [];
    new_prices.value = [];
    updated_prices.value = [];
    deleted_prices.value = [];
};

// Define Expose: Necessario para o componente pai conseguir aceder as
// propriedades do componente filho através da referencia.
defineExpose({
    form,
    new_prices,
    updated_prices,
    deleted_prices,
    clearData,
    loadData,
});
</script>
<style scoped>
.form-label {
    font-weight: 500;
    color: #333;
}
</style>
