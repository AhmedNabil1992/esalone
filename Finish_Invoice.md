بص ركز هنعمل دلوقتي تقفيل الحساب , بعد ما يدوس علي تقفيل الحساب انا كنت عامل ستورد بروسيدر بيقفل الحساب اول ما شاشة التقفيل تفتح هديك الكود علشان تعرف اللي كنت عامله قبل كده والاستورد بروسيدر برضو صفحة تقفيل الحساب فيها جدولين واحد علي اليمين فيه تفاصيل الوقت والتاني علي الشمال فيه تفاصيل الطلبات
using DevExpress.XtraEditors;
using DevExpress.XtraGrid;
using DevExpress.XtraReports.UI;
using MetroFramework;
using Microsoft.Data.SqlClient;
using PlayStation_TM.Main_Class;
using System;
using System.Collections.Generic;
using System.Data;
using System.Diagnostics;
using System.Globalization;
using System.Threading.Tasks;
using System.Windows.Forms;
using static System.Windows.Forms.VisualStyles.VisualStyleElement.TextBox;

namespace PlayStation_TM.Sales.POS
{
public partial class Close_Device : DevExpress.XtraEditors.XtraForm
{
DatabaseOperations conn = new DatabaseOperations();
string sql;

        int OrderNo, O_Result;
        string Invoice_No, IP_Address, Customer_ID, DeviceName;
        decimal Play_Cost = 0, Order_Cost = 0, Total_Cost = 0, Total_After_Discount = 0, Tax_AMT = 0, Services_AMT = 0, Final_Amount = 0, Remain_AMT = 0, discountAMT = 0, payedAMT = 0, depositAMT = 0;
        #region Helper Methods

        /// <summary>
        /// تحويل آمن من object إلى decimal
        /// </summary>
        private decimal SafeConvertToDecimal(object value, decimal defaultValue = 0)
        {
            if (value == null || value == DBNull.Value)
                return defaultValue;

            // ✅ معالجة النوع decimal مباشرة
            if (value is decimal decValue)
                return decValue;

            // ✅ معالجة أنواع الأرقام الأخرى
            if (value is int || value is long || value is double || value is float)
            {
                try
                {
                    return Convert.ToDecimal(value);
                }
                catch
                {
                    return defaultValue;
                }
            }

            // ✅ معالجة النصوص
            if (decimal.TryParse(value.ToString()?.Trim(), NumberStyles.Number, CultureInfo.InvariantCulture, out decimal result))
                return result;

            return defaultValue;
        }

        /// <summary>
        /// تحويل آمن من object إلى string
        /// </summary>
        private string SafeConvertToString(object value, string defaultValue = "")
        {
            return value == null || value == DBNull.Value ? defaultValue : value.ToString();
        }

        /// <summary>
        /// تحويل آمن من object إلى int
        /// </summary>
        private int SafeConvertToInt(object value, int defaultValue = 0)
        {
            if (value == null || value == DBNull.Value)
                return defaultValue;

            if (int.TryParse(value.ToString(), out int result))
                return result;

            return defaultValue;
        }

        /// <summary>
        /// قراءة آمنة من DataRow
        /// </summary>
        private T SafeGetFieldValue<T>(DataRow row, string fieldName, T defaultValue)
        {
            try
            {
                if (row == null || row[fieldName] == null || row[fieldName] == DBNull.Value)
                    return defaultValue;

                return row.Field<T>(fieldName);
            }
            catch
            {
                return defaultValue;
            }
        }

        /// <summary>
        /// تحويل آمن من string textbox إلى decimal
        /// </summary>
        private decimal GetDecimalValue(string text, decimal defaultValue = 0)
        {
            if (string.IsNullOrWhiteSpace(text))
                return defaultValue;

            if (decimal.TryParse(text, out decimal value))
                return value < 0 ? defaultValue : value;

            return defaultValue;
        }

        /// <summary>
        /// تسجيل الأخطاء في ملف
        /// </summary>
        private void LogError(string methodName, Exception ex)
        {
            try
            {
                string logPath = System.IO.Path.Combine(Application.StartupPath, "Logs", "Close_Device_errors.log");
                string logDir = System.IO.Path.GetDirectoryName(logPath);

                if (!System.IO.Directory.Exists(logDir))
                    System.IO.Directory.CreateDirectory(logDir);

                string message = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] [{methodName}] " +
                    $"Error: {ex.Message}\nStackTrace: {ex.StackTrace}\n\n";

                System.IO.File.AppendAllText(logPath, message, System.Text.Encoding.UTF8);
            }
            catch { }
        }

        #endregion
        public Close_Device(int Order_No, string Device_Name)
        {
            InitializeComponent();
            labelControl1.Text = "تقفيل " + Device_Name;
            OrderNo = Order_No;
            DeviceName = Device_Name;
        }
        #region Data_Source
        private void Fill_Play_Source()
        {
            sql = "Select * From play_wait Where play_wait.Order_No = @OrderNo";
            SqlParameter[] parameters = new SqlParameter[]
            {
                new SqlParameter("@OrderNo", SqlDbType.Int) { Value = OrderNo }
            };
            conn.LoadData(sql, gridControl1, parameters);
        }
        private void Fill_Play()
        {
            gridView1.Columns["ID"].Visible = false;
            gridView1.Columns["Order_No"].Caption = "رقم الأوردر";
            gridView1.Columns["Device_Name"].Caption = "إسم الجهاز";
            gridView1.Columns["Start_Time"].Caption = "وقت البدء";
            gridView1.Columns["End_Time"].Caption = "وقت الإنتهاء";
            gridView1.Columns["Period"].Caption = "المدة";
            gridView1.Columns["Cost"].Caption = "التكلفة";
            gridView1.Columns["Play_Type"].Caption = "نوع اللعب";
            gridView1.Columns["Play_Price"].Caption = "سعر الساعه";
            gridView1.Columns["User_Name"].Visible = false;
            gridView1.Columns["Shift"].Visible = false;

            gridView1.Columns["Start_Time"].DisplayFormat.FormatType = DevExpress.Utils.FormatType.Custom;
            gridView1.Columns["Start_Time"].DisplayFormat.FormatString = "hh:mm:ss tt";

            gridView1.Columns["End_Time"].DisplayFormat.FormatType = DevExpress.Utils.FormatType.Custom;
            gridView1.Columns["End_Time"].DisplayFormat.FormatString = "hh:mm:ss tt";

            GridColumnSummaryItem TTL_INV = new GridColumnSummaryItem();
            TTL_INV.SummaryType = DevExpress.Data.SummaryItemType.Sum;
            TTL_INV.FieldName = "Cost";
            TTL_INV.DisplayFormat = "{0:#.##}";

            gridView1.Columns["Cost"].Summary.Add(TTL_INV);

            gridView1.BestFitColumns();
        }
        private void Fill_Drink_Source()
        {
            sql = @"SELECT order_wait.ID, order_wait.Order_No, order_wait.Device_Name, item_master.Item_Name,
            order_wait.Quantity, order_wait.Price, order_wait.Amount, order_wait.Order_By, order_wait.Notes,
            CASE WHEN order_wait.[Print]='True' THEN 'تم' WHEN order_wait.[Print]='False' THEN 'لم يتم' ELSE '' END AS [Print]
            FROM dbo.order_wait
            LEFT JOIN dbo.item_master ON order_wait.Item_ID = item_master.Code
            WHERE order_wait.Order_No = @OrderNo";
            SqlParameter[] parameters = new SqlParameter[]
            {
                new SqlParameter("@OrderNo", SqlDbType.Int) { Value = OrderNo }
            };
            conn.LoadData(sql, gridControl2, parameters);
        }
        private void Fill_Drink()
        {
            gridView2.Columns["ID"].Visible = false;
            gridView2.Columns["Order_No"].Caption = "رقم الأوردر";
            gridView2.Columns["Device_Name"].Caption = "إسم الجهاز";
            gridView2.Columns["Item_Name"].Caption = "إسم الصنف";
            gridView2.Columns["Quantity"].Caption = "الكمية";
            gridView2.Columns["Price"].Caption = "السعر";
            gridView2.Columns["Amount"].Caption = "القيمة";
            gridView2.Columns["Order_By"].Caption = "طلب بواسطة";
            gridView2.Columns["Notes"].Caption = "ملاحظات";
            gridView2.Columns["Print"].Caption = "حالة الطباعة";

            GridColumnSummaryItem TTL_INV = new GridColumnSummaryItem();
            TTL_INV.SummaryType = DevExpress.Data.SummaryItemType.Sum;
            TTL_INV.FieldName = "Amount";
            TTL_INV.DisplayFormat = "{0:#.##}";

            gridView2.Columns["Amount"].Summary.Add(TTL_INV);

            gridView2.BestFitColumns();
        }
        private void Check_Customer()
        {
            try
            {
                sql = "Select * From devices Where devices.Order_No = @OrderNo And Customer_ID IS NOT NULL";
                SqlParameter[] parameters = new SqlParameter[]
                {
                    new SqlParameter("@OrderNo", SqlDbType.Int) { Value = OrderNo }
                };
                var dt = conn.GetData(sql, parameters);

                // ✅ FIX: التحقق من وجود صفوف قبل الوصول إليها
                if (dt != null && dt.Rows.Count > 0)
                {
                    Customer_ID = SafeGetFieldValue(dt.Rows[0], "Customer_ID", "0");

                    if (!string.IsNullOrEmpty(Customer_ID) && Customer_ID != "0")
                    {
                        sql = "Select * From customer Where customer.C_Card = @CustomerID";
                        SqlParameter[] parameters2 = new SqlParameter[]
                        {
                            new SqlParameter("@CustomerID", SqlDbType.VarChar, 255) { Value = Customer_ID }
                        };
                        var dt2 = conn.GetData(sql, parameters2);

                        if (dt2 != null && dt2.Rows.Count > 0)
                        {
                            labelControl11.Text = SafeGetFieldValue(dt2.Rows[0], "C_Name", "Unknown");
                            txt_Customer_Balance.Text = SafeGetFieldValue(dt2.Rows[0], "C_Money", 0m).ToString("0.00");
                        }
                        else
                        {
                            // ✅ العميل غير موجود في جدول customer
                            Customer_ID = "0";
                            labelControl11.Text = "عميل غير موجود";
                            txt_Customer_Balance.Text = "0.00";
                        }
                    }
                    else
                    {
                        // ✅ Customer_ID فارغ أو "0"
                        Customer_ID = "0";
                        labelControl11.Text = "";
                        txt_Customer_Balance.Text = "0.00";
                    }
                }
                else
                {
                    // ✅ لا يوجد Order_No في devices أو Customer_ID هو NULL
                    Customer_ID = "0";
                    labelControl11.Text = "";
                    txt_Customer_Balance.Text = "0.00";
                }
            }
            catch (Exception ex)
            {
                LogError("Check_Customer", ex);
                Customer_ID = "0";
                labelControl11.Text = "خطأ في تحميل بيانات العميل";
                txt_Customer_Balance.Text = "0.00";
            }
        }
        #endregion
        #region Event
        private void Close_Device_Load(object sender, EventArgs e)
        {
            // Show loading
            this.Enabled = false;

            // Execute operations
            Check_Time();

            // Handle validation
            if (O_Result == 1)
            {
                DialogResult dialogResult = MetroMessageBox.Show(this, "هل تريد تقفيل وقت الجهاز الأن ؟", "وقت الجهاز لم ينتهي", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
                if (dialogResult == DialogResult.Yes)
                {
                    Force_Close();
                }
                else
                {
                    this.Close();
                    return;
                }
            }
            else if (O_Result == 2)
            {
                Force_Close();
            }
            else if (O_Result == 3)
            {
                DialogResult dialogResult = MetroMessageBox.Show(this, "لم يتم تحديد وقت انتهاء مسبق , هل تريد تقفيل وقت الجهاز الأن ؟", "تقفيل وقت الجهاز ", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
                if (dialogResult == DialogResult.Yes)
                {
                    Force_Close();
                }
                else
                {
                    this.Close();
                    return;
                }
            }
            else if (O_Result == 4)
            {
                MetroMessageBox.Show(this, "يرجي إضافة وقت ,لقد تعديت الوقت المسموح به.", "الوقت الفعلي اكثر من المسجل.", MessageBoxButtons.OK, MessageBoxIcon.Error);
                this.Close();
                return;
            }
            else if (O_Result == 6)
            {
                MetroMessageBox.Show(this, "الجهاز مغلق بواسطة المستخدم.", "الجهاز مغلق.", MessageBoxButtons.OK, MessageBoxIcon.Error);
                this.Close();
                return;
            }

            Fill_Play_Source();
            Fill_Drink_Source();
            Get_Cost();
            Check_Customer();

            // Update UI
            Fill_Play();
            Fill_Drink();

            if (conn.HasPermission("إضافة خصم", "New") == true)
            {
                txt_Discount_AMT.ReadOnly = false;
            }

            timer1.Start();

            this.Enabled = true;
        }
        private void timer1_Tick(object sender, EventArgs e)
        {
            this.Close();
        }
        private void calcEdit1_EditValueChanged(object sender, EventArgs e)
        {
            payedAMT = GetDecimalValue(clc_Payed.Text, 0);

            if (payedAMT < 0)
            {
                clc_Payed.Text = "0";
                payedAMT = 0;
            }

            Calculate_Invoice();
        }
        private void simpleButton1_Click(object sender, EventArgs e)
        {
            //XtraMessageBox.Show(Remain_AMT.ToString());
            // Save Button
            if (conn.CheckAlarmSystemStatus(IP_Address) == "True")
            {
                // Get values safely
                decimal paid = GetDecimalValue(clc_Payed.Text);
                decimal discount = GetDecimalValue(txt_Discount_AMT.Text);
                decimal remain = GetDecimalValue(txt_Remain.Text);

                if (Remain_AMT < 0 && Customer_ID == "0")
                {
                    MetroMessageBox.Show(this, "يرجي التأكد من المبلغ المدفوع .", "خطأ !!", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                if (discount > Total_Cost)
                {
                    XtraMessageBox.Show("يرجي التأكد من قيمة الخصم.", "خطأ!", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                if (discount > 0 && string.IsNullOrWhiteSpace(txt_Reason.Text))
                {
                    XtraMessageBox.Show("يرجي التأكد من إدخال سبب الخصم.", "خطأ!", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                if (Customer_ID != "0")
                {
                    btnSave.Enabled = false;
                    Finish_Invoice();
                }
                else if (remain >= 0)
                {
                    btnSave.Enabled = false;
                    Finish_Invoice();
                }
                else
                {
                    MetroMessageBox.Show(this, "يرجي التأكد من المبلغ المدفوع.", "خطأ!!", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        private void gridView2_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.KeyCode == Keys.Delete)
            {
                if (conn.HasPermission("التحكم في الأجهزة", "Delete") == true)
                {
                    if(gridView2.RowCount > 0)
                    {
                        int focusedHandle = gridView1.FocusedRowHandle;

                        // Ignore non-data rows (group rows, headers, etc.)
                        if (!gridView1.IsDataRow(focusedHandle))
                        {
                            return;
                        }
                        if (gridView2.SelectedRowsCount == 1)
                        {
                            object cellValue = gridView2.GetRowCellValue(gridView2.FocusedRowHandle, "ID");
                            int orderId = SafeConvertToInt(cellValue, 0);

                            if (orderId > 0)
                            {
                                List<SqlParameter> parameters = new List<SqlParameter>();
                                parameters.Add(new SqlParameter("@I_Order_ID", SqlDbType.Int) { Value = orderId });

                                conn.ExecuteStoredProcedure(parameters, "Cancel_Order");
                                Fill_Drink_Source();
                                gridView2.RefreshData();
                                Get_Cost();
                            }
                        }
                    }
                }
                else
                {
                    conn.Permission_Error(this);
                }
            }
        }
        private void simpleButton2_Click(object sender, EventArgs e)
        {
            if (conn.HasPermission("التحكم في الأجهزة", "Edit") == true)
            {
                if (conn.CheckAlarmSystemStatus(IP_Address) == "True")
                {
                    List<SqlParameter> parameters = new List<SqlParameter>();
                    parameters.Add(new SqlParameter("@I_Order_No", SqlDbType.Int) { Value = OrderNo });
                    parameters.Add(new SqlParameter("@O_Result", SqlDbType.VarChar, 255) { Direction = ParameterDirection.Output });
                    parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });

                    conn.ExecuteStoredProcedure(parameters, "Cancel_Start_Time");
                    conn.ShowAlert(this, parameters[1].Value.ToString());
                    this.Close();
                }
            }
            else
            {
                conn.Permission_Error(this);
            }
        }
        private void calcEdit2_EditValueChanged(object sender, EventArgs e)
        {
            txt_Discount_AMT.Visible = true;
            txt_Reason.Visible = true;

            //int AMT_After = Convert.ToInt32((Convert.ToDecimal(textEdit3.Text) * Convert.ToDecimal(clc_Percent.Text)) / 100);
            //txt_Discount_AMT.Text = AMT_After.ToString();
            Calculate_Invoice();
        }
        private void labelControl8_Click(object sender, EventArgs e)
        {

        }
        #endregion
        #region Proceuders
        private void Check_Time()
        {
            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@I_Order_No", SqlDbType.VarChar) { Value = OrderNo });
            parameters.Add(new SqlParameter("@I_Device_Name", SqlDbType.VarChar) { Value = DeviceName });
            parameters.Add(new SqlParameter("@O_Result", SqlDbType.VarChar, 255) { Direction = ParameterDirection.Output });

            conn.ExecuteStoredProcedure(parameters, "Check_Close");

            O_Result = SafeConvertToInt(parameters[2].Value, 5);
        }
        private void Get_Cost()
        {
            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@G_Cost_Play", SqlDbType.Decimal) { Precision = 11, Scale = 2 , Direction = ParameterDirection.Output });
            parameters.Add(new SqlParameter("@G_Cost_Order", SqlDbType.Decimal) { Precision = 11, Scale = 2, Direction = ParameterDirection.Output });
            parameters.Add(new SqlParameter("@O_IP_Address", SqlDbType.VarChar,255) { Direction = ParameterDirection.Output });
            parameters.Add(new SqlParameter("@G_Order_No", SqlDbType.Int) { Value = OrderNo });
            parameters.Add(new SqlParameter("@O_Deposit", SqlDbType.Decimal) { Precision = 11, Scale = 2, Direction = ParameterDirection.Output });

            conn.ExecuteStoredProcedure(parameters, "Get_Cost_Close");

            depositAMT = SafeConvertToDecimal(parameters[4].Value, 0);
            txt_Deposit.Text = depositAMT.ToString("0.00");
            IP_Address = SafeConvertToString(parameters[2].Value, "127.0.0.1");
            labelControl8.Text = Total_Cost.ToString();
            Play_Cost = SafeConvertToDecimal(parameters[0].Value, 0);
            Order_Cost = SafeConvertToDecimal(parameters[1].Value, 0);
            Calculate_Invoice();
        }
        private void Force_Close()
        {
            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@I_Device_Name", SqlDbType.VarChar) { Value = DeviceName });

            conn.ExecuteStoredProcedure(parameters, "Close_Time_Force");
        }
        #endregion
        #region Print_Invoice
        private void Print_Invoice()
        {
            if (System_Configuration.Printer_Status == true)
            {
                if (System_Configuration.Print_Sales_Invoice == true)
                {
                    if(conn.HasPermission("التحكم في الأجهزة", "Print"))
                    {
                        XtraReport xtraReport1 = XtraReport.FromFile(System_Configuration.Sales_Report, true);
                        ReportPrintTool reportPrintTool1 = new ReportPrintTool(xtraReport1);
                        xtraReport1.Parameters["parameter1"].Value = Invoice_No;
                        xtraReport1.RequestParameters = false;
                        xtraReport1.ShowPrintMarginsWarning = false;
                        reportPrintTool1.PrinterSettings.Copies = 1;
                        reportPrintTool1.Print();
                    }
                }
            }
        }
        private void Re_Print()
        {
            if (System_Configuration.Printer_Status == true)
            {
                if (System_Configuration.Print_Again == true)
                {
                    if (conn.HasPermission("التحكم في الأجهزة", "Print"))
                    {
                        DialogResult dialogResult = MetroMessageBox.Show(this, "هل تريد طباعة فاتورة المبيعات مرة أخري ؟", "طباعة فاتورة المبيعات مرة أخري .", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
                        if (dialogResult == DialogResult.Yes)
                        {
                            if (System_Configuration.Print_Sales_Invoice == true)
                            {
                                XtraReport xtraReport1 = XtraReport.FromFile(System_Configuration.Sales_Report, true);
                                ReportPrintTool reportPrintTool1 = new ReportPrintTool(xtraReport1);
                                xtraReport1.Parameters["parameter1"].Value = Invoice_No;
                                xtraReport1.RequestParameters = false;
                                xtraReport1.ShowPrintMarginsWarning = false;
                                reportPrintTool1.PrinterSettings.Copies = 1;
                                reportPrintTool1.Print();
                            }
                        }
                    }
                }
            }
        }
        #endregion
        #region Action
        private void Calculate_Invoice()
        {
            try
            {
                // Calculate basic costs
                Total_Cost = Play_Cost + Order_Cost;
                Total_After_Discount = Total_Cost - discountAMT;
                Final_Amount = Total_After_Discount;

                // Calculate services fee
                if (System_Configuration.Services_Play == true)
                {
                    Services_AMT = Total_After_Discount * (decimal)(System_Configuration.Services_Value / 100.0);
                    Services_AMT = Math.Round(Services_AMT, 2); // round to 2 decimal places
                    txtServices.Text = Services_AMT.ToString("0.00");
                }
                else
                {
                    Services_AMT = 0;
                    txtServices.Text = "0.00";
                }

                // Calculate tax
                if (System_Configuration.Tax_Play == true)
                {
                    Tax_AMT = Math.Round((Total_After_Discount + Services_AMT) * (decimal)(System_Configuration.Tax_Value / 100.0), 2);
                    txtTax.Text = Tax_AMT.ToString("0.00");
                }
                else
                {
                    Tax_AMT = 0;
                    txtTax.Text = "0.00";
                }

                // Calculate final amount and remaining
                Final_Amount = Math.Round(Tax_AMT + Services_AMT + Total_After_Discount - depositAMT, 2);

                Remain_AMT = Math.Round(payedAMT - Final_Amount, 2);

                // Update UI
                txt_Remain.Text = Remain_AMT.ToString("0.00");
                labelControl8.Text = Final_Amount.ToString("0.00");
            }
            catch (Exception ex)
            {
                LogError("Calculate_Invoice", ex);
                // Set default values in case of error
                txt_Remain.Text = "0.00";
                labelControl8.Text = "0.00";
            }
        }
        private void Finish_Invoice()
        {
            //Force_Close();
            Insert_Invoice();
            if (string.IsNullOrEmpty(Invoice_No))
            {
                throw new Exception("فشل في إنشاء رقم الفاتورة");
            }
            Insert_Services();
            Insert_Tax();

            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@I_Device_Name", SqlDbType.VarChar) { Value = DeviceName });
            parameters.Add(new SqlParameter("@I_Order_No", SqlDbType.Int) { Value = OrderNo });
            parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });
            parameters.Add(new SqlParameter("@I_Inv_No", SqlDbType.VarChar) { Value = Invoice_No });

            conn.ExecuteStoredProcedure(parameters, "Finish_Invoice");

            Add_Discount();
            if (Customer_ID != "0")
            {
                Customer_Balance();
            }
            Print_Invoice();
            Re_Print();
            conn.ShowAlert(this, "تم تقفيل الجهاز .");
            this.Close();
        }
        private void Insert_Invoice()
        {
            decimal discount = GetDecimalValue(txt_Discount_AMT.Text, 0);

            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@I_Amount", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = Total_Cost });
            parameters.Add(new SqlParameter("@I_Discount", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = discount });
            parameters.Add(new SqlParameter("@I_Tax", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = Tax_AMT });
            parameters.Add(new SqlParameter("@I_Services", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = Services_AMT });
            parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });
            parameters.Add(new SqlParameter("@I_Customer_ID", SqlDbType.VarChar) { Value = Customer_ID });
            parameters.Add(new SqlParameter("@O_Invoice_No", SqlDbType.VarChar, 255) { Direction = ParameterDirection.Output });

            conn.ExecuteStoredProcedure(parameters, "Insert_New_Invoice");

            Invoice_No = SafeConvertToString(parameters[6].Value, "");

            if (string.IsNullOrEmpty(Invoice_No))
            {
                throw new Exception("فشل في الحصول على رقم الفاتورة من قاعدة البيانات");
            }
        }
        private void Insert_Tax()
        {
            if(System_Configuration.Tax_Play == true)
            {
                List<SqlParameter> parameters = new List<SqlParameter>();
                parameters.Add(new SqlParameter("@I_Invoice_No", SqlDbType.VarChar) { Value = Invoice_No });
                parameters.Add(new SqlParameter("@I_Amount", SqlDbType.Decimal) { Precision = 11, Size = 2, Value = Tax_AMT });
                parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });

                conn.ExecuteStoredProcedure(parameters, "Insert_Tax_History");
            }
        }
        private void Insert_Services()
        {
            if(System_Configuration.Services_Play== true)
            {
                List<SqlParameter> parameters = new List<SqlParameter>();
                parameters.Add(new SqlParameter("@I_Invoice_No", SqlDbType.VarChar) { Value = Invoice_No });
                parameters.Add(new SqlParameter("@I_Amount", SqlDbType.Decimal) { Precision = 11, Size = 2, Value = Services_AMT });
                parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });

                conn.ExecuteStoredProcedure(parameters, "Insert_Services_History");
            }
        }
        private void Add_Discount()
        {
            decimal discount = GetDecimalValue(txt_Discount_AMT.Text, 0);

            if (discount > 0)
            {
                List<SqlParameter> parameters = new List<SqlParameter>();
                parameters.Add(new SqlParameter("@Invoice_No", SqlDbType.VarChar) { Value = Invoice_No });
                parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });
                parameters.Add(new SqlParameter("@I_Reason", SqlDbType.VarChar) { Value = txt_Reason.Text });
                parameters.Add(new SqlParameter("@I_AMT", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = discount });

                conn.ExecuteStoredProcedure(parameters, "Add_Discount");
            }
        }
        private void Customer_Balance()
        {
            decimal customerBalance = GetDecimalValue(txt_Customer_Balance.Text, 0);
            decimal paidAmount = GetDecimalValue(clc_Payed.Text, 0);
            decimal finalAmount = GetDecimalValue(labelControl8.Text, 0);

            if (finalAmount > (customerBalance + paidAmount))
            {
                MetroMessageBox.Show(this, "رصيد العميل أقل من المبلغ المطلوب", "يرجي الإنتباه!!", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }

            List<SqlParameter> parameters = new List<SqlParameter>();
            parameters.Add(new SqlParameter("@I_Customer_ID", SqlDbType.VarChar, 255) { Value = Customer_ID });
            parameters.Add(new SqlParameter("@I_Invoice_No", SqlDbType.VarChar) { Value = Invoice_No });
            parameters.Add(new SqlParameter("@I_Amount", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = Final_Amount });
            parameters.Add(new SqlParameter("@I_Username", SqlDbType.VarChar) { Value = Sessions.Username });
            parameters.Add(new SqlParameter("@I_PaidAmount", SqlDbType.Decimal) { Precision = 11, Scale = 2, Value = paidAmount });

            conn.ExecuteStoredProcedure(parameters, "Insert_Customer_Pay");
        }
        #endregion
        private void txt_Discount_AMT_EditValueChanged(object sender, EventArgs e)
        {
            discountAMT = GetDecimalValue(txt_Discount_AMT.Text, 0);

            if (discountAMT < 0)
            {
                txt_Discount_AMT.Text = "0";
                discountAMT = 0;
            }

            if (discountAMT > Total_Cost)
            {
                txt_Discount_AMT.Text = "0";
                discountAMT = 0;
                MetroMessageBox.Show(this, "لا يمكن تطبيق خصم أكثر من حساب الفاتورة", "خطأ في الخصم.", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            txt_Reason.Visible = discountAMT > 0;

            Calculate_Invoice();
        }
    }

}
ALTER PROCEDURE [dbo].[Check_Close]
@I_Order_No int,
@I_Device_Name varchar(255),
@O_Result varchar(255) OUTPUT
AS
BEGIN
SET NOCOUNT ON;

    DECLARE @Device_Kind varchar(255),
    					@Play_Kind varchar(255),
    					@Device_End_Time datetime,
    					@Extra_Time INT;

    SELECT @Device_Kind = Kind, @Play_Kind = Play_Cate, @Device_End_Time = End_Time
    FROM devices
    WHERE Device_Name = @I_Device_Name;
    	SELECT @Extra_Time = system_config.[Value] FROM system_config WHERE system_config.Description = 'Extra_Time';

    IF (@Device_Kind = 'Time')
    BEGIN
        IF (@Play_Kind = 'وقت محدد')
        BEGIN
            IF (@Device_End_Time > GETDATE())
            BEGIN
                SET @O_Result = '1';
            END
            ELSE
            BEGIN
    						IF(DATEDIFF(MINUTE, @Device_End_Time, GETDATE()) >= @Extra_Time)
    						BEGIN
    							SET @O_Result = '4';
    						END
    						ELSE
    						BEGIN
                SET @O_Result = '2';
    						END
            END
        END
        ELSE IF (@Play_Kind = 'مفتوح')
        BEGIN
            SET @O_Result = '3';
        END
    END
    ELSE
    BEGIN
        SET @O_Result = '5';
    END

END

ALTER PROCEDURE [dbo].[Close_Time_Force]
@I_Device_Name varchar(100)
AS
BEGIN
SET NOCOUNT ON;
-- Test Update 4
DECLARE @O_Time_Limit INT,
@O_Order_No INT,
@O_Device_Start_Time datetime,
@O_Play_Wait_ID INT,
@O_Session_Start datetime,
@O_Session_Period INT,
@O_Price DECIMAL(11,2),
@O_Play_Type nvarchar(max),
@O_Limit_Check INT,
@O_Order_Count INT,
@O_Period INT,
@O_End_Time datetime,
@O_Time_Type nvarchar(max),
@O_Time_End nvarchar(max),
@O_Before_Time INT;

SELECT
@O_Time_Type = d.Play_Cate,
@O_End_Time = d.End_Time,
@O_Order_No = d.Order_No,
@O_Time_Limit = d.Limit_Time,
@O_Device_Start_Time = d.Start_Time
FROM devices d
WHERE d.Device_Name = @I_Device_Name;
SELECT @O_Play_Wait_ID = MAX(play_wait.ID) FROM play_wait WHERE play_wait.Order_No = @O_Order_No;
SELECT @O_Session_Start = play_wait.Start_Time , @O_Play_Type = play_wait.Play_Type FROM play_wait WHERE play_wait.ID = @O_Play_Wait_ID;
SELECT @O_Order_Count = COUNT(play_wait.ID) FROM play_wait WHERE play_wait.Order_No = @O_Order_No;

IF @O_End_Time >= GETDATE()
BEGIN
SET @O_Time_End = 'False';
SELECT 'False';
END
ELSE
BEGIN
SET @O_Time_End = 'True';
SELECT 'True';
END

SET @O_Before_Time = DATEDIFF(minute,@O_Device_Start_Time,@O_Session_Start);
SET @O_Period = DATEDIFF(minute,@O_Device_Start_Time,GETDATE()); -- Play Time From BEGIN
SET @O_Session_Period = DATEDIFF(minute,@O_Session_Start,GETDATE()); -- Play Time FOR current session (Switch)
if(@O_Session_Period = -1)
BEGIN
SET @O_Session_Period = 0;
END
EXEC @O_Price = [dbo].[Get_Play_Price] @I_Device_Name, @O_Play_Type, @O_Session_Period, @O_Session_Start;
-- Check Limit Time
SET @O_Limit_Check = DATEDIFF(MINUTE, @O_Device_Start_Time, GETDATE());

-- Case 1 SPECIFIC Time
-- Case 1.1 Limit Time Check
-- كده انا خليت دي في حالة انو لسه معداش الوقت المحدد
IF (@O_Limit_Check < @O_Time_Limit)
BEGIN
IF (@O_Order_Count > 1)
BEGIN
UPDATE play_wait
SET
Period = CASE WHEN dbo.tround((@O_Time_Limit - @O_Before_Time), 'Close') = -1 THEN 0
ELSE dbo.tround((@O_Time_Limit - @O_Before_Time), 'Close')
END,
Play_Price = @O_Price,
End_Time = GETDATE(),
Cost = dbo.mround(
(
CASE WHEN dbo.tround((@O_Time_Limit - @O_Before_Time), 'Close') = -1 THEN 0
ELSE dbo.tround((@O_Time_Limit - @O_Before_Time), 'Close')
END
) \* (@O_Price / 60),
'Close'
)
WHERE ID = @O_Play_Wait_ID;

        SELECT 'Case1';
    END
    ELSE
    BEGIN
        UPDATE play_wait SET Period = @O_Time_Limit, Play_Price = @O_Price, End_Time = GETDATE(), Cost = dbo.mround((@O_Time_Limit * (@O_Price / 60)), 'Close') WHERE ID = @O_Play_Wait_ID;
        SELECT 'Case2';
    END

END
ELSE
-- من هنا اول ما يعدي الوقت المحدد
IF (@O_Time_Type = 'وقت محدد')
BEGIN
IF (@O_Time_End = 'True')
BEGIN
SET @O_Session_Period = DATEDIFF(MINUTE, @O_Session_Start, @O_End_Time);
if(@O_Session_Period = -1)
BEGIN
SET @O_Session_Period = 0;
END
UPDATE play_wait SET Period = dbo.tround(@O_Session_Period, 'Close'), Play_Price = @O_Price, End_Time = @O_End_Time, Cost = dbo.mround((dbo.tround(@O_Session_Period, 'Close') _ (@O_Price / 60)), 'Close') WHERE ID = @O_Play_Wait_ID;
SELECT 'Case3';
END
ELSE
BEGIN
UPDATE play_wait SET Period = dbo.tround(@O_Session_Period, 'Close'), Play_Price = @O_Price, End_Time = GETDATE(), Cost = dbo.mround((dbo.tround(@O_Session_Period, 'Close') _ (@O_Price / 60)), 'Close') WHERE ID = @O_Play_Wait_ID;
SELECT 'Case4';
END
END
ELSE
BEGIN
UPDATE play_wait SET Period = dbo.tround(@O_Session_Period, 'Close') , Play_Price = @O_Price, End_Time = GETDATE(), Cost = dbo.mround((dbo.tround(@O_Session_Period, 'Close') \* (@O_Price / 60)), 'Close') WHERE ID = @O_Play_Wait_ID;
SELECT 'Case5';
END
END

ALTER PROCEDURE [dbo].[Finish_Invoice]
@I_Device_Name VARCHAR(MAX),
@I_Order_No int,
@I_Username VARCHAR(MAX),
@I_Inv_No VARCHAR(50)

AS
BEGIN

SET NOCOUNT ON;

DECLARE @Market_Check int,
@O_Shift_No int;

    EXEC Check_Shift @I_Username, @O_Shift_No OUTPUT;

SELECT @Market_Check = COUNT(\*) FROM order_wait WHERE Order_No = @I_Order_No;

-- Insert Play History INTO play_history FROM play_wait.
INSERT INTO play_history (Device_Name, Play_Type, Hour_Price, Start_Time, End_Time, Play_Time, Cost, Username, Shift_No, TRX_Date, Invoice_No)
SELECT pw.Device_Name, pw.Play_Type, pw.Play_Price, pw.Start_Time, pw.End_Time, pw.Period, pw.Cost, @I_Username, @O_Shift_No, CONVERT(date, GETDATE()), @I_Inv_No
FROM play_wait pw
LEFT JOIN devices d ON pw.Device_Name = d.Device_Name
WHERE d.Kind = 'Time' AND pw.Order_No = @I_Order_No;

-- Insert play trx_history.
INSERT INTO trx_history (TRX_Name, Amount, TRX_Type, Shift, Username, Reference)
SELECT dt.Name, pw.Cost, 'لعب', @O_Shift_No, @I_Username, @I_Inv_No
FROM play_wait pw
LEFT JOIN devices d ON pw.Device_Name = d.Device_Name
LEFT JOIN Device_Type dt ON d.Device_Type = dt.ID
WHERE d.Kind = 'Time' AND pw.Order_No = @I_Order_No;

-- Insert market history and market trx_history, if applicable.
IF @Market_Check > 0 BEGIN
-- Insert market history into market_history table from order_wait.
-- INSERT INTO market_history (Client_Name, Item_ID, Quantity, Price, Username, Shift, Invoice_No)
-- SELECT ow.Device_Name, ow.Item_ID, ow.Quantity, ow.Price, @I_Username, @O_Shift_No, @I_Inv_No FROM order_wait ow JOIN devices d ON ow.Device_Name = d.Device_Name
-- JOIN item_master im ON ow.Item_ID = im.Code WHERE ow.Order_No = @I_Order_No;
--
-- -- Insert market trx_history into trx_history table from order_wait.
-- INSERT INTO trx_history (TRX_Name, Amount, TRX_Type, Shift, Username, Reference )
-- SELECT
-- im.Item_Name, ow.Amount, 'مبيعات', @O_Shift_No, @I_Username, @I_Inv_No FROM order_wait ow JOIN devices d ON ow.Device_Name = d.Device_Name
-- JOIN item_master im ON ow.Item_ID = im.Code WHERE ow.Order_No = @I_Order_No;

INSERT INTO market_history (Client_Name, Item_ID, Quantity, Price, Username, Shift, Invoice_No)
SELECT
ow.Device_Name,
ow.Item_ID,
SUM(ow.Quantity) AS Quantity,
ow.Price AS Price,
@I_Username,
@O_Shift_No,
@I_Inv_No
FROM order_wait ow
JOIN devices d ON ow.Device_Name = d.Device_Name
JOIN item_master im ON ow.Item_ID = im.Code
WHERE ow.Order_No = @I_Order_No
GROUP BY
ow.Device_Name,
ow.Item_ID,
ow.Price;
INSERT INTO trx_history (TRX_Name, Amount, TRX_Type, Shift, Username, Reference)
SELECT
im.Item_Name,
SUM(ow.Amount) AS Amount,
'مبيعات',
@O_Shift_No,
@I_Username,
@I_Inv_No
FROM order_wait ow
JOIN devices d ON ow.Device_Name = d.Device_Name
JOIN item_master im ON ow.Item_ID = im.Code
WHERE ow.Order_No = @I_Order_No
GROUP BY
im.Item_Name;

END

-- Delete entries from play_wait and order_wait tables for the current order.
DELETE FROM play_wait WHERE Order_No = @I_Order_No;
DELETE FROM order_wait WHERE Order_No = @I_Order_No;

-- Set the device status to OFF using PS_Control procedure.
EXEC PS_Control @I_Device_Name, @I_Username, 'OFF', 'Finish Time';
-- Set the output variables.
EXEC Reset_Device @I_Device_Name;
END

ALTER PROCEDURE [dbo].[Insert_New_Invoice]
@I_Amount AS decimal (11,2) ,
@I_Discount AS decimal (11,2),
@I_Tax AS decimal (11,2),
@I_Services AS decimal (11,2),
@I_Username AS VARCHAR(MAX) ,
@I_Customer_ID VARCHAR(50),
@O_Invoice_No AS VARCHAR(MAX) OUTPUT
AS
BEGIN
-- routine body goes here, e.g.
-- SELECT 'Navicat for SQL Server'

DECLARE @InsertedRows TABLE (Invoice_No VARCHAR(20));
DECLARE @Shift_No INT;

EXEC Check_Shift @I_Username, @Shift_No OUTPUT;

INSERT INTO invoices (Invoice_No,Shift_No,Amount,Discount,Tax,Services,Username,Customer_ID)
OUTPUT inserted.Invoice_No INTO @InsertedRows
VALUES (dbo.GenerateInvoiceNo(), @Shift_No, @I_Amount, @I_Discount,@I_Tax,@I_Services,@I_Username,@I_Customer_ID);

SELECT @O_Invoice_No = Invoice_No FROM @InsertedRows;
END

ALTER PROCEDURE [dbo].[Insert_Tax_History]
@I_Invoice_No VARCHAR(MAX),
@I_Amount DECIMAL(11,2),
@I_Username VARCHAR(MAX)

AS
BEGIN
SET NOCOUNT ON;
DECLARE @O_Shift_No INT;
DECLARE @TRX_ID TABLE (TRX_ID INT);
EXEC Check_Shift @I_Username, @O_Shift_No OUTPUT;
INSERT INTO trx_history(trx_history.TRX_Type,trx_history.TRX_Name,trx_history.Amount,trx_history.Shift,trx_history.Username,Reference)
OUTPUT INSERTED.ID INTO @TRX_ID VALUES
('ضريبة','ضريبة',@I_Amount,@O_Shift_No,@I_Username,@I_Invoice_No);
INSERT INTO tax_history(Invoice_No,Amount,Shift_No,Username,TRX_ID)
SELECT
@I_Invoice_No,@I_Amount,@O_Shift_No,@I_Username,TRX_ID FROM @TRX_ID;
END

ALTER PROCEDURE [dbo].[Insert_Services_History]
@I_Invoice_No VARCHAR(MAX),
@I_Amount DECIMAL(11,2),
@I_Username VARCHAR(MAX)

AS
BEGIN
SET NOCOUNT ON;
DECLARE @O_Shift_No INT;
DECLARE @TRX_ID TABLE (TRX_ID INT);
EXEC Check_Shift @I_Username, @O_Shift_No OUTPUT;
INSERT INTO trx_history(trx_history.TRX_Type,trx_history.TRX_Name,trx_history.Amount,trx_history.Shift,trx_history.Username,Reference)
OUTPUT INSERTED.ID INTO @TRX_ID VALUES
('خدمة','خدمة',@I_Amount,@O_Shift_No,@I_Username,@I_Invoice_No);
INSERT INTO services_history(Invoice_No,Amount,Shift_No,Username,TRX_ID)
SELECT
@I_Invoice_No,@I_Amount,@O_Shift_No,@I_Username,TRX_ID FROM @TRX_ID;
END

ALTER PROCEDURE [dbo].[Add_Discount] (
@Invoice_No VARCHAR(50),
@I_Username text,
@I_Reason text,
@I_AMT decimal(11,2)
)
AS
BEGIN
-- Declare variables
DECLARE @O_Shift_No int;
DECLARE @TRX_ID TABLE (
TRX_ID INT
);

    -- Get the current open shift number

SELECT @O_Shift_No = MAX(shift_mgt.Shift_No) FROM shift_mgt WHERE shift_mgt.Status = 'Open';

    -- Insert a row into the trx_history table

INSERT INTO trx_history (TRX_Type, TRX_Name, TRX_Date, TRX_Time, Amount, Shift, Username,Reference)
OUTPUT INSERTED.ID INTO @TRX_ID
VALUES ('خصم', @I_Reason, Convert(date, Getdate()), Convert(time, Getdate()), @I_AMT, @O_Shift_No, @I_Username , @Invoice_No);
-- Insert a row into the discount_history table
INSERT INTO discount_history (Invoice_No, Amount, Reason, Username, Date, Time, Shift_No, TRX_ID)
SELECT @Invoice_No, @I_AMT, @I_Reason, @I_Username, Convert(date, Getdate()), Convert(time, Getdate()), @O_Shift_No, TRX_ID
FROM @TRX_ID;

END

ALTER PROCEDURE [dbo].[Insert_Customer_Pay]
@I_Customer_ID AS VARCHAR(255),
@I_Invoice_No AS VARCHAR(50),
@I_Amount AS DECIMAL(11,2),
@I_Username AS VARCHAR(50),
@I_PaidAmount AS DECIMAL(11,2)
AS
BEGIN
BEGIN TRANSACTION; -- Start transaction

    DECLARE @O_Customer_Balance DECIMAL(11,2), @O_Customer_Name VARCHAR(255), @Shift_No INT;
    DECLARE @TRX_ID TABLE (TRX_ID INT);
    DECLARE @TRX_ID2 TABLE (TRX_ID2 INT);

    SELECT @O_Customer_Name = C_Name, @O_Customer_Balance = C_Money
    FROM customer
    WHERE C_Card = @I_Customer_ID;

    EXEC Check_Shift @I_Username, @Shift_No OUTPUT;

    IF (@O_Customer_Name IS NOT NULL)
    BEGIN
        -- When customer pays an amount
        IF (@I_PaidAmount > 0)
        BEGIN
            IF (@I_Amount = @I_PaidAmount)
            BEGIN
                -- Case 1: Customer pays exact amount (balance becomes zero)
                UPDATE customer SET customer.C_Point = customer.C_Point + @I_Amount WHERE customer.C_Card = @I_Customer_ID;
            END
            ELSE IF (@O_Customer_Balance > 0)
            BEGIN
                -- Case 1: Customer has positive balance and pay remain amount
                IF (@I_PaidAmount + @O_Customer_Balance = @I_Amount)
                BEGIN
                    PRINT 'Case 2.1';
                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                    ('عملاء', 'سحب رصيد', @O_Customer_Balance, @Shift_No, @I_Username, @I_Invoice_No);

                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'سحب', (@O_Customer_Balance * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                    FROM @TRX_ID;

                    UPDATE customer
                    SET C_Money = 0, C_Point = C_Point + @I_Amount
                    WHERE C_Card = @I_Customer_ID;
                END
                -- Case 2: Customer overpays (balance becomes positive)
                ELSE IF (@I_PaidAmount + @O_Customer_Balance > @I_Amount)
                BEGIN
                    PRINT 'Case 2.2';
                    -- Calculate the remaining balance after the invoice is paid
                    DECLARE @RemainingBalance DECIMAL(11,2);
                    SET @RemainingBalance = @I_PaidAmount + @O_Customer_Balance - @I_Amount;

                    -- Log the deduction for the invoice amount into trx_history
                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                    ('عملاء', 'سحب رصيد', @I_Amount, @Shift_No, @I_Username, @I_Invoice_No);

                    -- Log the deduction into customer_history
                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'سحب', (@I_Amount * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                    FROM @TRX_ID;

                    -- Log the overpayment into trx_history
                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID2 VALUES
                    ('عملاء', 'إضافة رصيد', @RemainingBalance, @Shift_No, @I_Username, @I_Invoice_No);

                    -- Log the overpayment into customer_history
                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'إضافة', @RemainingBalance, @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID2
                    FROM @TRX_ID2;

                    -- Update the customer's remaining balance and points
                    UPDATE customer
                    SET C_Money = @RemainingBalance, C_Point = C_Point + @I_Amount
                    WHERE C_Card = @I_Customer_ID;
                END
                -- Case 3: Partial payment leading to insufficient funds but customer has positive balance
                ELSE IF (@I_PaidAmount + @O_Customer_Balance < @I_Amount)
                BEGIN
                    PRINT 'Case 2.3';

                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                    ('عملاء', 'سحب رصيد', @O_Customer_Balance, @Shift_No, @I_Username, @I_Invoice_No);

                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'سحب', (@O_Customer_Balance * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                    FROM @TRX_ID;

                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID2 VALUES
                    ('عملاء', 'مديونية', @I_Amount - @O_Customer_Balance - @I_PaidAmount, @Shift_No, @I_Username, @I_Invoice_No);

                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'مديونية', ((@I_Amount - @O_Customer_Balance - @I_PaidAmount) * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID2
                    FROM @TRX_ID2;

                    -- Update customer balance and points
                    UPDATE customer
                    SET C_Money = ((@I_Amount - @O_Customer_Balance - @I_PaidAmount) * -1), C_Point = C_Point + @I_Amount
                    WHERE C_Card = @I_Customer_ID;
                END
            END
            ELSE IF (@O_Customer_Balance <= 0)
            BEGIN
                IF(@I_PaidAmount > @I_Amount)
                BEGIN
                    PRINT 'Case 3.1';
                    -- Log the overpayment into trx_history
                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID2 VALUES
                    ('عملاء', 'إضافة رصيد', (@I_PaidAmount - @I_Amount), @Shift_No, @I_Username, @I_Invoice_No);

                    -- Log the overpayment into customer_history
                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'إضافة', (@I_PaidAmount - @I_Amount), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID2
                    FROM @TRX_ID2;

                    -- Update the customer's remaining balance and points
                    UPDATE customer
                    SET C_Money = C_Money + (@I_PaidAmount - @I_Amount), C_Point = C_Point + @I_Amount
                    WHERE C_Card = @I_Customer_ID;
                END
                IF(@I_PaidAmount < @I_Amount)
                BEGIN
                    PRINT 'Case 3.2';

                    INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                    OUTPUT INSERTED.ID INTO @TRX_ID2 VALUES
                    ('عملاء', 'مديونية', @I_Amount - @I_PaidAmount, @Shift_No, @I_Username, @I_Invoice_No);

                    INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                    SELECT @I_Customer_ID, 'مديونية', ((@I_Amount - @I_PaidAmount) * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID2
                    FROM @TRX_ID2;

                    -- Update customer balance and points
                    UPDATE customer
                    SET C_Money = C_Money - (@I_Amount - @I_PaidAmount), C_Point = C_Point + @I_Amount
                    WHERE C_Card = @I_Customer_ID;
                END
            END
        END
        -- When customer does not pay any amount
        ELSE IF (@I_PaidAmount = 0)
        BEGIN
            -- Case 1: Customer has negative balance لو العميل رصيده اقل من صفر
            IF (@O_Customer_Balance < 0)
            BEGIN
    							PRINT 'Case 4.1';
                INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                ('عملاء', 'مديونية', @I_Amount, @Shift_No, @I_Username, @I_Invoice_No);

                INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                SELECT @I_Customer_ID, 'مديونية', (@I_Amount * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                FROM @TRX_ID;

                UPDATE customer
                SET C_Money = C_Money - @I_Amount, C_Point = C_Point + @I_Amount
                WHERE C_Card = @I_Customer_ID;
            END
            -- Case 2: Customer has positive balance
            ELSE IF (@O_Customer_Balance > 0 AND @O_Customer_Balance >= @I_Amount)
            BEGIN
    							PRINT 'Case 4.2';
                INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                ('عملاء', 'سحب رصيد', @I_Amount, @Shift_No, @I_Username, @I_Invoice_No);

                INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                SELECT @I_Customer_ID, 'سحب', (@I_Amount * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                FROM @TRX_ID;

                UPDATE customer
                SET C_Money = C_Money - @I_Amount, C_Point = C_Point + @I_Amount
                WHERE C_Card = @I_Customer_ID;
            END
            -- Case 3 : Customer Has positive balance and not cover amount
            ELSE IF (@O_Customer_Balance > 0 AND @O_Customer_Balance <= @I_Amount)
            BEGIN
    							PRINT 'Case 4.3';
                INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                ('عملاء', 'سحب رصيد', @O_Customer_Balance, @Shift_No, @I_Username, @I_Invoice_No);

                INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                SELECT @I_Customer_ID, 'سحب', (@O_Customer_Balance * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                FROM @TRX_ID;

                -- clear table

                INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                OUTPUT INSERTED.ID INTO @TRX_ID2 VALUES
                ('عملاء', 'مديونية', (@I_Amount - @O_Customer_Balance), @Shift_No, @I_Username, @I_Invoice_No);

                INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                SELECT @I_Customer_ID, 'مديونية', ((@I_Amount - @O_Customer_Balance) * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID2
                FROM @TRX_ID2;

                UPDATE customer
                SET C_Money = C_Money - @I_Amount, C_Point = C_Point + @I_Amount
                WHERE C_Card = @I_Customer_ID;
            END
            -- Case 4: Customer has zero balance
            ELSE IF (@O_Customer_Balance = 0)
            BEGIN
    							PRINT 'Case 4.4';
                INSERT INTO trx_history (TRX_Type, TRX_Name, Amount, Shift, Username, Reference)
                OUTPUT INSERTED.ID INTO @TRX_ID VALUES
                ('عملاء', 'مديونية', @I_Amount, @Shift_No, @I_Username, @I_Invoice_No);

                INSERT INTO customer_history (Customer_ID, Type, Amount, Username, Shift_No, Remark, TRX_ID)
                SELECT @I_Customer_ID, 'مديونية', (@I_Amount * -1), @I_Username, @Shift_No, CONCAT('فاتورة رقم ', @I_Invoice_No), TRX_ID
                FROM @TRX_ID;

                UPDATE customer
                SET C_Money = C_Money - @I_Amount, C_Point = C_Point + @I_Amount
                WHERE C_Card = @I_Customer_ID;
            END
        END
    END

    COMMIT TRANSACTION; -- Commit transaction

END;
