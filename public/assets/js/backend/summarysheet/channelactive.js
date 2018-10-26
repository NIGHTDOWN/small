define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'summary_sheet/channel_active/index',
                    export_url: 'summary_sheet/channel_active/export',
                    table: 'summary_sheet',
                }
            });

            // 版本列表
            var version_list = $.getJSON('summary_sheet.summary_sheet/versionList');
            // 渠道列表
            var channel_list = $.getJSON('summary_sheet.summary_sheet/channelList');
            // 操作类型
            var operate_list = $.getJSON('summary_sheet.channel_active/operateType');

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
                        {field: 'operate_type', title: __('Operate_type'), searchList: operate_list, visible: false, defaultValue: 'active'},
                        {field: 'show_time', title: __('展示方式'), searchList: {0:'天', 1:'周', 2:'月'}, visible: false, defaultValue: 0},
                    ]
                ]
            });

            echart.on('post-body.bs.table', function (e, settings, json, xhr) {
                // 数据
                var data_list = settings;
                // 基于准备好的dom，初始化echarts实例
                var myChart = Echarts.init(document.getElementById('echart'), 'walden');
                // 类型
                var series_list = new Array();
                for (var i in data_list.operate_data) {
                    series_list.push({
                        name: i,
                        type:'line',
                        data: data_list.operate_data[i]
                    });
                }
                // 指定图表的配置项和数据
                var option = {
                    title: {
                        text: '渠道统计'
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

                // 添加天/周/月按钮

            })

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
                Layer.confirm("确认导出数据<form action='" + Fast.api.fixurl("summary_sheet/channel_active/export") + "' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'></form>", {
                    title: '导出数据',
                    btn: ["确认"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }, yes: function (index, layero) {
                        submitForm(all, layero);
                        Layer.close(index);
                        // submitForm(all, layero);
                        // return false;
                    }
                });
            });

            // 为表格绑定事件
            Table.api.bindevent(echart);
            // Table.api.bindevent(table);
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