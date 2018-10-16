define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/putplanuploadrecord/index',
                    table: 'video_put_plan_upload_record',
                }
            });

            var table = $("#table");

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
                        {field: 'file_name', title: __('File_name')},
                        {field: 'error_info', title: __('Error_info'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: function (data) {
                                if (data == '0') {
                                    return '待处理';
                                } else if (data == '1') {
                                    return '完成';
                                } else {
                                    return '错误';
                                }
                            },
                            searchList: {
                                '0' : __('待处理'),
                                '1' : __('完成'),
                                '2' : __('错误'),
                            }
                        },
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});