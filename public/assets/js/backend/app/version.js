define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'app/version/index',
                    add_url: 'app/version/add',
                    edit_url: 'app/version/edit',
                    del_url: 'app/version/del',
                    multi_url: 'app/version/multi',
                    table: 'app_version',
                }
            });

            var table = $("#table");

            var  status_text = {
                '-1' : '删除',
                '0' : '关闭',
                '1'  : '开启',
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'app_version', title: __('App_version'),operate:false},
                        {field: 'app_version_code', title: __('版本代码'),operate:false},
                        {
                            field: 'update_type',
                            title: __('Update_type'),
                            formatter:function (data) {
                                if(data==1){
                                    return '提示更新'
                                }else{
                                    return '强制更新'
                                }
                            },
                            operate:false
                        },
                        {field: 'create_admin', title: __('创建用户'),operate:false},
                        {field: 'create_time', title: __('Create_time'),operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'),operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter:function (data) {
                                return status_text[data]
                            },
                            operate:false
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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