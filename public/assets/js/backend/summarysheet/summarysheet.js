define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'summarysheet/summarysheet/index',
                    export_url: 'summarysheet/summarysheet/export',
                    table: 'summary_sheet',
                }
            });

            // 版本列表
            var version_list = $.getJSON('summarysheet.summarysheet/versionList');
            // 渠道列表
            var channel_list = $.getJSON('summarysheet.summarysheet/channelList');
            // 操作类型
            var operate_list = $.getJSON('summarysheet.summarysheet/operateType');

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
                var legend = new Array();
                for (var i in data_list.list) {
                    series_list.push({
                        name: i + data_list.operate,
                        type:'line',
                        data: data_list.list[i]
                    });
                    legend.push(i + data_list.operate);
                }
                // 指定图表的配置项和数据
                var option = {
                    title: { // 标题
                        text: '渠道统计'
                    },
                    tooltip: { // 坐标数据显示
                        trigger: 'axis',
                    },
                    legend: { // 坐标轴提示器
                        bottom: 0,
                        data:legend
                    },
                    xAxis: { // x轴数据
                        data: data_list.time_data
                    },
                    yAxis: {},
                    series: series_list, // 数据组
                };
                // 使用刚指定的配置项和数据显示图表。
                myChart.setOption(option);
            })

            // 导出
            var submitForm = function (ids, layero, flag) {
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
                $("input[name=flag]", layero).val(flag);
                $("form", layero).submit();
            };
            $(document).on("click", ".btn-export", function () {
                var ids = Table.api.selectedids(echart);
                var page = echart.bootstrapTable('getData');
                var all = echart.bootstrapTable('getOptions').totalRows;
                console.log(ids, page, all);
                Layer.confirm("确认导出数据<form action='" + Fast.api.fixurl("summarysheet/summarysheet/export") + "' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'><input type='hidden' name='flag'></form>", {
                    title: '导出数据',
                    btn: [ "按筛选导出", "渠道统计日报表"], // , "渠道统计总报表"
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }, yes: function (index, layero) {
                        submitForm(all, layero, 3);
                        Layer.close(index);
                        return false;
                    }, btn2: function (index, layero) {
                        submitForm(all, layero, 1);
                        Layer.close(index);
                        return false;
                    }, btn3: function (index, layero) {
                        submitForm(all, layero, 2);
                        Layer.close(index);
                        return false;
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