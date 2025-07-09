export default function usePriceCalculations() {
    /**
     * Devolve uma string com o valor do preço sem VAT
     *
     * @param {Object} data - Objeto com dados de custo e VAT
     * @returns {String}
     */
    const getPriceWithoutVat = (data) => {
        if (data.costData?.price && typeof data.vat === "number") {
            const vatRate = data.vat / 100;
            const basePrice = data.costData.price / (1 + vatRate);
            return basePrice.toFixed(2) + "€";
        }
        return " - ";
    };

    /**
     * Devolve uma string com o valor do VAT em euros
     *
     * @param {Object} data - Objeto com dados de custo e VAT
     * @returns {String}
     */
    const getVatValue = (data) => {
        if (data.costData?.price && typeof data.vat === "number") {
            const vatRate = data.vat / 100;
            const basePrice = data.costData.price / (1 + vatRate);
            const vatAmount = data.costData.price - basePrice;
            return vatAmount.toFixed(2) + "€";
        }
        return " - ";
    };

    return {
        getPriceWithoutVat,
        getVatValue
    };
}
