import $axios from '../api.js'

const state = () => ({
    customers: [], //UNTUK MENAMPUNG DATA CUSTOMER YANG DI-REQUEST
    products: [], //UNTUK MENAMPUNG DATA PRODUCT YANG DI-REQUEST
    transaction: [], //TAMBAHKAN STATE INI
    page: 1
})

const mutations = {
    //MENGUBAH STATE CUSTOMER BERDASARKAN DATA YANG DITERIMA
    ASSIGN_DATA(state, payload) {
        state.customers = payload
    },
    //MENGUBAH STATE PRODUCT BERDASARKAN DATA YANG DITERIMA
    DATA_PRODUCT(state, payload) {
        state.products = payload
    },
    SET_PAGE(state, payload) {
        state.page = payload
    },
    ASSIGN_TRANSACTION(state, payload) {
        state.transaction = payload
    },
}

const actions = {
    //MENGIRIM PERMINTAAN KE SERVER UNTUK MENGAMBIL DATA CUSTOMER BERDASARKAN KEYWORD YANG BERADA DI DALAM PAYLOAD.SEARCH
    getCustomers({ commit, state }, payload) {
        let search = payload.search
        payload.loading(true)
        return new Promise((resolve, reject) => {
            $axios.get(`/customer?page=${state.page}&q=${search}`)
            .then((response) => {
                //JIKA BERHASIL, SIMPAN DATANYA KE STATE
                commit('ASSIGN_DATA', response.data)
                payload.loading(false)
                resolve(response.data)
            })
        })
    },
    //MENGIRIM PERMINTAAN KE SERVER UNTUK MENGMABIL DATA PRODUCT, MEKANISMENYA SAMA DENGAN FUNGSI DIATAS
    getProducts({ commit, state }, payload) {
        let search = payload.search
        payload.loading(true)
        return new Promise((resolve, reject) => {
            $axios.get(`/product?page=${state.page}&q=${search}`)
            .then((response) => {
                //APABILA BERHASIL, SIMPAN KE STATE PRODUCTS
                commit('DATA_PRODUCT', response.data)
                payload.loading(false)
                resolve(response.data)
            })
        })
    },
    //FUNGSI UNTUK MEMBUAT TRANSAKSI
    createTransaction({commit}, payload) {
        return new Promise((resolve, reject) => {
            //MENGIRIM PERMINTAAN KE SERVER UNTUK MEMBUAT TRANSAKSI
            $axios.post(`/transaction`, payload)
            .then((response) => {
                resolve(response.data)
            })
        })
    },
    detailTransaction({ commit }, payload) {
        //MENGIRIM PERMINTAAN KE SERVER UNTUK MENGAMBIL DATA BERDASARKAN ID TRANSAKSI
        return new Promise((resolve, reject) => {
            $axios.get(`/transaction/${payload}/edit`)
            .then((response) => {
                //DATANYA KITA SIMPAN KE DALAM STATE TRANSACTION MENGGUNAKAN MUTATION
                commit('ASSIGN_TRANSACTION', response.data.data)
                resolve(response.data)
            })
        })
    },
    completeItem({ commit }, payload) {
        return new Promise((resolve, reject) => {
            $axios.post(`/transaction/complete-item`, payload)
            .then((response) => {
                resolve(response.data)
            })
        })
    },
    payment({ commit }, payload) {
        return new Promise((resolve, reject) => {
            $axios.post(`/transaction/payment`, payload)
            .then((response) => {
                resolve(response.data)
            })
        })
    },
}

export default {
    namespaced: true,
    state,
    actions,
    mutations
}