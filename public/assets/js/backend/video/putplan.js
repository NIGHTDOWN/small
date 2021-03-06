define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/putplan/index',
                    del_url: 'video/putplan/del',
                    set_param: 'video/putplan/set_param',
                    batch_start: 'video/putplan/batch_start',
                    batch_cancel: 'video/putplan/batch_cancel',
                    upload_relation_table: 'video/putplan/upload_relation_table',
                    play: 'video/putplan/play',
                    table: 'video_put_plan',
                }
            });

            var table = $("#table");
            var statusText = {0: '未定时', 1: '已定时', 2: '已发布'};
            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'status', title: __('Status'), searchList: statusText, formatter: function (data) {return statusText[data];}},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'plan_time', title: __('Plan_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'put_time', title: __('Put_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'video/putplan/play'
                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            Controller.api.buttons(table);
        },
        play: function () {
            Controller.api.bindevent();
        },
        set_param: function () {
            Controller.api.bindevent();
        },
        upload_relation_table: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function (table) {
                Form.api.bindevent($("form[role=form]"));
            },
            buttons: function (table) {
                //Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                //Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                //Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                // 设置参数
                $(toolbar).on('click', '.btn-set_param', function () {
                    var url = options.extend.set_param;
                    Fast.api.open(url, __('设置参数'));
                });
                // 导入关联表
                $(toolbar).on('click', '.btn-upload_relation_table', function () {
                    var url = options.extend.upload_relation_table;
                    Fast.api.open(url, __('导入关联表'));
                });
                // 批量开始
                $(toolbar).on('click', '.btn-batch_start', function () {
                    Layer.confirm(
                        __('确定开始选中计划吗?'),
                        {icon: 3, title: __('Warning'), shadeClose: true},
                        function (index) {
                            Controller.api.method.sendAjax(index, options.extend.batch_start, {ids: Table.api.selectedids(table)});
                        }
                    );
                });
                // 批量取消
                $(toolbar).on('click', '.btn-batch_cancel', function () {
                    Layer.confirm(
                        __('确定取消选中计划吗?'),
                        {icon: 3, title: __('Warning'), shadeClose: true},
                        function (index) {
                            Controller.api.method.sendAjax(index, options.extend.batch_cancel, {ids: Table.api.selectedids(table)});
                        }
                    );
                });
            },
            method: {
                sendAjax: function (index, url, data = {}) {
                    Fast.api.ajax({
                        url: url,
                        type: 'POST',
                        data: data
                    }, function (data, result) {
                        Layer.close(index);
                        $('.btn-refresh').trigger('click');
                    }, function (data, result) {
                        Layer.close(index);
                    })
                }
            }
        }
    };
    return Controller;
});