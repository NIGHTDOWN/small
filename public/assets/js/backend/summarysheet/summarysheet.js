define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'summary_sheet.summary_sheet/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'summary_sheet',
                    // echart_url: 'summary_sheet.summary_sheet/addEchart',
                }
            });
            //
            // var table = $("#table");
            // 版本列表
            var version_list = $.getJSON('summary_sheet.summary_sheet/versionList');
            // 渠道列表
            var channel_list = $.getJSON('summary_sheet.summary_sheet/channelList');
            // 操作类型
            var operate_list = $.getJSON('summary_sheet.summary_sheet/operateType');
            //
            // // 初始化表格
            // table.bootstrapTable({
            //     url: $.fn.bootstrapTable.defaults.extend.index_url,
            //     pk: 'id',
            //     sortName: 'id',
            //     search: false,
            //     showExport: true,
            //     commonSearch: true,
            //     searchFormVisible: true,
            //     showToggle: false,
            //     showColumns: false,
            //     visible: false,
            //     columns: [
            //         [
            //             {field: 'id', title: __('Id'), operate: false, visible: false},
            //             {field: 'day', title: __('Day_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
            //             {field: 'register', title: __('Register'), operate: false}, //, visible: false
            //             {field: 'activate', title: __('Activate'), operate: false}, //, visible: false
            //             {field: 'register_total', title: __('Register_total'), operate: false}, // , visible: false
            //             {field: 'activate_total', title: __('Activate_total'), operate: false}, // visible: false,
            //
            //             {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, visible: false, operate: false},
            //
            //             {field: 'create_time', title: __('Create_time'), visible: false, operate: false},
            //             {field: 'channel_id', title: __('Channel_name'), searchList: channel_list, visible: false},
            //             {field: 'version_id', title: __('Version_name'), searchList: version_list, visible: false},
            //             {field: 'operate_type', title: __('Operate_type'), searchList: operate_list, visible: false},
            //         ]
            //     ]
            // });

            // 图表表格初始化
            var echart = $('#echart');
            echart.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                // searchFormVisible: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                // commonSearch: false,
                commonSearch: true,
                searchFormVisible: true,
                columns: [
                    [
                        {field: 'day', title: __('Day_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'channel_id', title: __('Channel_name'), searchList: channel_list, visible: false},
                        {field: 'version_id', title: __('Version_name'), searchList: version_list, visible: false, operate: false},
                        {field: 'operate_type', title: __('Operate_type'), searchList: operate_list, visible: false, defaultValue: 'activate'},
                        {field: 'show_time', title: __('展示方式'), searchList: {0:'天', 1:'周', 2:'月'}, visible: false, defaultValue: 0}
                    ]
                ]
            });

            echart.on('post-body.bs.table', function (e, settings, json, xhr) {
                console.log(1, settings)
                // 数据
                var data_list = settings;
                // 基于准备好的dom，初始化echarts实例
                var myChart = Echarts.init(document.getElementById('echart'), 'walden');
                // 类型
                var series_list = new Array();
                for (var i in data_list.operate_data) {
                    var name = '';
                    if (i == 'activate') {
                        name = '激活量';
                    } else if (i == 'register') {
                        name = '注册量';
                    }
                }
                series_list.push({
                    name:name,
                    type:'line',
                    data: data_list.operate_data[i] //[321,432,543,376,286,298,700],
                });
                //
                // data_list.operate_data.operate_type.forEach(function (val, i) {
                //     if (val == '激活量') {
                //         series_list.push({
                //             name:'激活量',
                //             type:'line',
                //             data: data_list.operate_data.activate //[200,312,431,241,175,275,369], // 数据
                //         })
                //     } else if (val == '注册量') {
                //         series_list.push({
                //             name:'注册量',
                //             type:'line',
                //             data: data_list.operate_data.register //[321,432,543,376,286,298,700],
                //         })
                //     } else if (val == '启动量') {
                //         series_list.push({
                //             name:'启动量',
                //             type:'line',
                //             data: data_list.operate_data.active //[321,432,543,376,286,298,700],
                //         })
                //     }
                // });

                // 指定图表的配置项和数据   // 找的最基础的入门示例
                var option = {
                    title: {
                        text: '新增用户分析'
                    },
                    tooltip: {},
                    legend: {
                        data:['']
                    },
                    xAxis: {
                        data: data_list.time_data // X轴
                    },
                    yAxis: {},
                    series: series_list,
                };
                // 使用刚指定的配置项和数据显示图表。
                myChart.setOption(option);
            });

            // 导出
            var submitForm = function (ids, layero) {
                var options = echart.bootstrapTable('getOptions');
                console.log(options);
                var columns = [];
                $.each(options.columns[0], function (i, j) {
                    if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                        columns.push(j.field);
                    }
                });
                var search = options.queryParams({});
                $("input[name=search]", layero).val(options.searchText);
                $("input[name=filter]", layero).val(search.filter);
                $("input[name=op]", layero).val(search.op);
                $("input[name=columns]", layero).val(columns.join(','));
                $("form", layero).submit();
            };
            $(document).on("click", ".btn-export", function () {
                var ids = Table.api.selectedids(echart);
                var page = echart.bootstrapTable('getData');
                var all = echart.bootstrapTable('getOptions').totalRows;
                console.log(ids, page, all);
                Layer.confirm("确认导出数据<form action='" + Fast.api.fixurl("summary_sheet/summary_sheet/export") + "' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'></form>", {
                    title: '导出数据',
                    btn: ["确认"],
                    success: function (layero, index) {
                        console.log(111111)
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }, yes: function (index, layero) {
                        console.log(222222)
                        submitForm(all, layero);
                        Layer.close(index);
                        // submitForm(all, layero);
                        // return false;
                    }
                });
            });

            // 为表格绑定事件
            // Table.api.bindevent(table);
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