define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'user/user/tableBaseData',
        async:false,
        success:function (data) {
            base_data = data;
        }
    });

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
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
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
                        {field: 'burse.coin', title: '金币',sortable:true},
                        {field: 'fans_total', title: '粉丝',sortable:true},
                        {field: 'group_id', title: '用户组', searchList: base_data.group_list,formatter:function (value) {
                            return base_data.group_list[value];
                        }},
                        {field: 'is_robot', title: '机器人', searchList: base_data.is_robot,formatter:function (value) {
                            return base_data.is_robot[value];
                        }},
                        {field: 'status', title: __('Status'),searchList: base_data.status_list,formatter:function (value) {
                            return base_data.status_list[value];
                        }},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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