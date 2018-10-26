define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/stat/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'video',
                    echart_url: 'video/stat/chartdata'
                }
            });


            // 图表表格初始化
            var echart = $('#echart');
            echart.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.echart_url,
                // searchFormVisible: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                // commonSearch: false,
                commonSearch: true,
                searchFormVisible: true,
                columns: [
                    [
                        {field: 'date_range', title: __('日期'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'category_id', title: __('分类'),column:'category_id',addclass:'selectpage', data:'data-source="category/selectpage"  data-params="{\'custom[type]\':\'video\'}"  data-field="name"'}
                    ]
                ]
            });

            echart.on('post-body.bs.table', function (e, settings, json, xhr) {
                //初始化
                var myChart = Echarts.init(document.getElementById('echart'), 'walden');

                //组装数据
                var series_list = [];
                Object.keys(settings.data).forEach(function(key){
                    var value=settings.data[key];
                    series_list.push({
                        name:value.name,
                        type:'line',
                        data: value.list
                    })
                });

                //参数配置
                var option = {
                    title: {
                        text: '视频统计'
                    },
                    tooltip: {},
                    legend: {
                        data:['']
                    },
                    xAxis: {
                        type:'category',
                        data: settings.date_list
                    },
                    yAxis: {},
                    series: series_list
                };
                myChart.setOption(option);
            });

            // 为表格绑定事件
            Table.api.bindevent(echart);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});