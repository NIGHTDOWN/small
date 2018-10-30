define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'message/sms/index',
                    add_url: 'message/sms/add',
                    edit_url: 'message/sms/edit',
                    del_url: 'message/sms/del',
                    // multi_url: 'message/sms/multi',
                    table: 'sms_template',
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
                        {field: 'template_code', title: __('Template_code')},
                        {field: 'template_content', title: __('Template_content'),operate:false},
                        {field: 'create_time', title: __('Create_time'),sortable:true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'),sortable:true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'create_task',
                                    title: __('创建发送短信任务'),
                                    text: __('创建任务'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'message/sms_task/add?template_code={template_code}'
                                }
                            ]
                        }
                    ]
                ]
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