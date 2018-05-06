import './index.scss'


new Vue({
    el: '#app',
    data: {
        swipeList: [],
        sixList: []
    },
    methods: {
        getSwipe() {
            axios.post(ROOT + "/weixin/index/carousel").then(res => {
                let data = res.data;
                if (data.code === 200) {
                    this.swipeList = data.list;
                } else {
                    this.MessageBox("错误", data.msg);
                }
            });
        },
        getList() {
            axios.post(ROOT+"/weixin/index/index").then(res => {
                let data = res.data;
                if (data.code === 200) {
                    this.sixList = data.list;
                } else {
                    this.MessageBox("错误", data.msg);
                }
            });
        },
        routerPush(code, type) {
            let typeArr = ["route", "ticket", "hotel"];
            this.$router.push(`/${typeArr[type - 1]}/${code}`);
        }
    },
    mounted() {
        this.getSwipe();
        this.getList();
    }
})
