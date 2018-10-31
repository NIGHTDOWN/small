define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'user/user/tableBaseData',
        async:false,
        success:function (data) {
            base_data = data;
        }
    });

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    edit_url: 'user/user/edit',
                    // del_url: 'user/user/del',
                    set_robot_param: 'user/user/set_robot_param',
                    set_active_param: 'user/user/set_active_param',
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
                        {field: 'head_img', title: __('Avatar'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'create_time', title: __('Createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'update_time', title: __('Updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'last_login', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'fans_total', title: '粉丝',sortable:true,operate: 'BETWEEN'},
                        {field: 'burse.coin', title: '金币',sortable:true,operate: 'BETWEEN'},
                        {field: 'burse.coin_earning_total', title: '累计获得金币',sortable:true,operate: 'BETWEEN'},
                        {field: 'burse.withdraw_coin', title: '累计提现金币',sortable:true,operate: 'BETWEEN'},
                        {field: 'group_id', title: '用户组', searchList: base_data.group_list,formatter:function (value) {
                            return base_data.group_list[value];
                        }},
                        {field: 'is_robot', title: '机器人', searchList: {0:'否',1:'是'},formatter:function (value) {
                            return base_data.is_robot[value];
                        }},
                        {field: 'status', title: __('Status'),searchList: {0:'禁用',1:'正常'},formatter:function (value) {
                            return base_data.status_list[value];
                        }},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            Controller.api.buttons(table);
        },
        edit: function () {
            Controller.api.bindevent();
        },
        set_robot_param: function () {
            Controller.api.bindevent();
        },
        set_active_param: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            buttons: function (table) {
                // Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                // Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                // Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                // 设置机器人参数
                $(toolbar).on('click', '.btn-set_robot_param', function () {
                    var url = options.extend.set_robot_param;
                    Fast.api.open(url, __('设置机器人参数'));
                });
                $(toolbar).on('click', '.btn-set_active_param', function () {
                    var url = options.extend.set_active_param;
                    Fast.api.open(url, __('活跃值任务设置'));
                });
            }
        }
    };
    return Controller;
});