<template>
    <el-form :model="form" ref="formRef" label-position="top" class="space-y-5">
        <!-- =============== PROVIDER Form ================ -->
        <!-- Custom Label + Tabs (Fora do  el-form-item) -->
        <div class="flex items-center space-x-2 mb-1">
            <img :src="providerIcon" alt="icon" class="w-4 h-4" />
            <span class="form-label">Providers</span>
            <!-- Tabs -->
            <div v-if="editMode || form.costs.length > 0" class="flex items-center space-x-2 ml-4">
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
        <el-form-item prop="providers" class="pricesDropdown">
            <el-select
                v-model="form.providers_by_year[selectedYearTab]"
                multiple
                placeholder="Choose Providers"
                :collapse-tags="(form.providers_by_year[selectedYearTab] || []).length > 4"
                clearable
                filterable
                @change="handleCosts"
            >
                <el-option v-for="item in providersList" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
        </el-form-item>

        <!-- Provider NEW Costs Table -->
        <el-form-item v-if="filteredNewCosts.length > 0">
            <div class="space-y-4 w-full">
                <div
                    v-for="(cost, index) in filteredNewCosts"
                    :key="cost.provider_id + '_' + cost.year"
                    class="grid grid-cols-3 gap-4 items-center"
                >
                    <!-- Provider Name -->
                    <span class="font-medium">
                        {{ getProviderNameById(cost.provider_id) }}
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

        <!-- Provider EXISTING COSTS TABLE -->
        <div v-if="filteredCosts.length > 0" class="table-container-providers">
            <el-table :data="filteredCosts" style="width: 100%">
                <el-table-column
                    v-for="col in costsTableColumns"
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
                                @change="handleCostChange(row)"
                            />
                        </span>
                        <!-- Se for as categorias -->
                        <span v-if="col.property === 'category_id'" class="block truncate" :title="col.formatter(row)">
                            {{ col.formatter(row) }}
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
import providerIcon from "@/Icons/provider.svg?url";
import vatIcon from "@/Icons/vat.svg?url";
import noVatIcon from "@/Icons/noVat.svg?url";

defineOptions({
    name: "ProductProvidersCosts",
});

// Define as props
const props = defineProps({
    data: Object,
    editMode: Boolean,
    providersList: Array,
});

// Variaveis Ref comuns ao componente
const formRef = ref(null);
const availableYears = ref([]); // Anos disponiveis segundo os existentes nos dados recebidos (dinamico) sobre o provider
const selectedYearTab = ref(""); // tab do ano selecionado sobre o provider
const new_costs = ref([]);
const updated_costs = ref([]);
const deleted_costs = ref([]);
const form = ref({
    id: "",
    providers: [],
    providers_by_year: {},
    costs: [],
});

//Variaveis Nao Reactivas nao precisam de 'ref', pois nao sao alteradas.
const costsTableColumns = [
    {
        label: "Provider Name",
        property: "provider_id",
        formatter: (row) => (row.provider_id && row.provider ? row.provider.name : ""),
        icon: providerIcon,
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

const filteredCosts = computed(() => {
    return form.value.costs.filter((cost) => cost.year === Number(selectedYearTab.value));
});

const filteredNewCosts = computed(() => {
    const year = Number(selectedYearTab.value);
    return props.editMode ? new_costs.value.filter((cost) => cost.year === year) : new_costs.value;
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
        manageYears(form.value.costs, availableYears, selectedYearTab);
    }
};

const getProviderNameById = (id) => {
    // Obtem o nome do provider com base no id
    const provider = props.providersList.find((p) => p.id === id);
    return provider ? provider.name : `Provider ${id}`;
};

const handleCosts = (selectedProviders) => {
    const currentYear = Number(selectedYearTab.value); // Ano selecionado

    // Crio uma copia para prevenir alterar o array originial
    const currentProviders = [...(selectedProviders || [])];

    // Se existem custos associados ou estamos em modo de edicao.
    if (form.value.costs.length > 0 || props.editMode) {
        // ids dos fornecedores ja guardados na BD
        const savedProvidersInYear = form.value.costs
            .filter((cost) => cost.year === currentYear)
            .map((cost) => cost.provider_id);

        // ids dos novos fornecedores selecionados
        const newProvidersInYear = new_costs.value
            .filter((cost) => cost.year === currentYear)
            .map((cost) => cost.provider_id);

        // Adiciono apenas novos providers (nao se encontra no array "new" nem nos "saved"-guardados na bd)
        currentProviders.forEach((providerId) => {
            const isSaved = savedProvidersInYear.includes(providerId);
            const isNew = newProvidersInYear.includes(providerId);

            // Se não é novo nem existia na BD entao adiciono ao array de novos custos desse provider-product
            if (!isSaved && !isNew) {
                new_costs.value.push({
                    provider_id: providerId,
                    price: 0,
                    year: currentYear,
                });
            }
        });

        // Removo "Novos" providers que tenham sido deselecionados.
        // Isso vai eliminar da lista `new_costs` qualquer item cujo provider não esteja mais selecionado
        new_costs.value = new_costs.value.filter((cost) => {
            const sameYear = cost.year === currentYear;
            const stillSelected = currentProviders.includes(cost.provider_id);
            return !sameYear || stillSelected;
        });

        // Removo Providers que ja estavam guardados na BD e que foram deselecionados
        // Removo da tabela de custos associados
        // Adiciono o custo associado removido ao array de eliminados (deleted_costs)
        form.value.costs = form.value.costs.filter((cost) => {
            const isSameYear = cost.year === currentYear;

            // Para outros anos, nao considero. Apenas ano atualmente selecionado
            if (!isSameYear) return true;

            const stillSelected = selectedProviders.includes(cost.provider_id);

            // Se o provider existente for removido movo para o array de eliminados
            if (!stillSelected) {
                deleted_costs.value.push(cost);
            }

            return stillSelected;
        });
    } else {
        const newProviderIds = new_costs.value.map((cost) => cost.provider_id);
        // Adiciono novo custo apenas se o provider ja nao estiver selecionado.
        currentProviders.forEach((providerId) => {
            const isNew = newProviderIds.includes(providerId);
            if (!isNew) {
                new_costs.value.push({
                    provider_id: providerId,
                    price: 0,
                    year: new Date().getFullYear(),
                });
            }
        });

        // Removo "Novos" providers que tenham sido deselecionados.
        new_costs.value = new_costs.value.filter((cost) => currentProviders.includes(cost.provider_id));
    }
};

const handleCostChange = (cost) => {
    // Verifico se o custo ja foi alterado anteriormente
    const exists = updated_costs.value.find((c) => c.provider_id === cost.provider_id && c.year === cost.year);

    // Senao foi alterado entao adicionamos a associacao com a alteracao ao array updated
    if (!exists) {
        updated_costs.value.push({
            provider_id: cost.provider_id,
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
    // Faço uma copia dos dados do provider
    const obj = { ...row.provider };

    return getPriceWithoutVat({
        ...obj,
        costData: { ...row },
    });
};

const getVatValueFormat = (row) => {
    // Obtem o valor de VAT em euros
    // Faço uma copia dos dados do provider
    const obj = { ...row.provider};

    return getVatValue({
        ...obj,
        costData: { ...row },
    });
};

const clearData = () => {
    // Limpa todos os dados.
    form.value.providers = [];
    form.value.providers_by_year = {};
    form.value.costs = [];
    new_costs.value = [];
    updated_costs.value = [];
    deleted_costs.value = [];
};

// Define Expose: Necessario para o componente pai conseguir aceder as
// propriedades do componente filho através da referencia.
defineExpose({
    form,
    new_costs,
    updated_costs,
    deleted_costs,
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
