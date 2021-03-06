define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'message/sys/index',
                    add_url: 'message/sys/add',
                    // edit_url: 'message/sys/edit',
                    // del_url: 'message/sys/del',
                    multi_url: 'message/sys/multi',
                    table: 'sys_message',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),sortable:true},
                        {field: 'message', title: __('Message')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                        {field: 'send_time', title: __('Send_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                        {field: 'send_total', title: __('Send_total')},
                        {field: 'view_total', title: __('View_total')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: '查看',
                                    title: __('查看'),
                                    text: __('查看'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'message/sys/detail'
                                },
                                {
                                    name: '编辑',
                                    title: __('编辑'),
                                    text: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    hidden:function (row) {
                                        if (row.status === 1){
                                            return true;
                                        }
                                        return false;
                                    },
                                    url: 'message/sys/edit'
                                },
                                {
                                    name: '发送',
                                    text:'发送',
                                    title: '发送',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    hidden:function (row) {
                                        if (row.status !== 0){
                                            return true;
                                        }
                                        return false;
                                    },
                                    confirm:'确认发送?',
                                    url: 'message/sys/send',
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            
            // 绑定TAB事件
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).closest("ul").data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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