define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    //状态文本
    var status_text={0: '禁用',1: '正常'};
    //类型文本
    var type_text={1:'普通用户', 2: '大V用户'};
    //是否机器人文本
    var is_robot_text={0:'否', 1: '是'};

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user_name', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'head_img_url', title: __('Avatar'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'create_time', title: __('Createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'update_time', title: __('Updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'last_login', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'burse.coin', title: '金币',sortable:true,sortName:'aaa'},
                        {field: 'fans_total', title: '粉丝',sortable:true},
                        {field: 'type', title: '用户类型', searchList: type_text,formatter:function (value) {
                            return type_text[value];
                        }},
                        {field: 'is_robot', title: '机器人', searchList: is_robot_text,formatter:function (value) {
                            return is_robot_text[value];
                        }},
                        {field: 'status', title: __('Status'),searchList: status_text,formatter:function (value) {
                            return status_text[value];
                        }},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'set_vip',
                                    text:'设置vip',
                                    title: '设置vip',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    hidden:function (row) {
                                        if (row.type === 2){
                                            return true;
                                        }
                                        return false;
                                    },
                                    confirm:'确认设置?',
                                    url: 'user/user/editVip/action/1',
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'cancel_vip',
                                    text:'取消vip',
                                    title: '取消vip',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    hidden:function (row) {
                                        if (row.type === 1){
                                            return true;
                                        }
                                        return false;
                                    },
                                    confirm:'确认取消?',
                                    url: 'user/user/editVip/action/0',
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
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