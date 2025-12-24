#include "ClassFlowControl.h"

#include "connect_wlan.h"
#include "read_wlanini.h"

#include "freertos/task.h"

#include <sys/stat.h>

#ifdef __cplusplus
extern "C" {
#endif
#include <dirent.h>
#ifdef __cplusplus
}
#endif

#include "ClassLogFile.h"
#include "time_sntp.h"
#include "Helper.h"
#include "server_ota.h"

#include "server_help.h"
#include "MainFlowControl.h"
#include "basic_auth.h"
#include "../../include/defines.h"

static const char* TAG = "FLOWCTRL";

//#define DEBUG_DETAIL_ON

std::string ClassFlowControl::doSingleStep(std::string _stepname, std::string _host)
{
    std::string _classname = "";
    std::string result = "";

    ESP_LOGD(TAG, "Step %s start", _stepname.c_str());

    if ((_stepname.compare("[TakeImage]") == 0) || (_stepname.compare(";[TakeImage]") == 0)) {
        _classname = "ClassFlowTakeImage";
    }
	
    if ((_stepname.compare("[Alignment]") == 0) || (_stepname.compare(";[Alignment]") == 0)) {
        _classname = "ClassFlowAlignment";
    }
	
    if ((_stepname.compare(0, 7, "[Digits") == 0) || (_stepname.compare(0, 8, ";[Digits") == 0)) {
        _classname = "ClassFlowCNNGeneral";
    }
	
    if ((_stepname.compare("[Analog]") == 0) || (_stepname.compare(";[Analog]") == 0)) {
        _classname = "ClassFlowCNNGeneral";
    }
	
	
    #ifdef ENABLE_WEBHOOK
        if ((_stepname.compare("[Webhook]") == 0) || (_stepname.compare(";[Webhook]") == 0)) {
            _classname = "ClassFlowWebhook";
        }
    #endif //ENABLE_WEBHOOK

    for (int i = 0; i < FlowControl.size(); ++i) {
        if (FlowControl[i]->name().compare(_classname) == 0) {
            if (!(FlowControl[i]->name().compare("ClassFlowTakeImage") == 0)) {
                // if it is a TakeImage, the image does not need to be included, this happens automatically with the html query.
                FlowControl[i]->doFlow("");
            }
		
            result = FlowControl[i]->getHTMLSingleStep(_host);
        }
    }

    ESP_LOGD(TAG, "Step %s end", _stepname.c_str());

    return result;
}

std::string ClassFlowControl::TranslateAktstatus(std::string _input)
{
    if (_input.compare("ClassFlowTakeImage") == 0) {
        return ("Take Image");
    }

    if (_input.compare("ClassFlowAlignment") == 0) {
        return ("Aligning");
    }

    if (_input.compare("ClassFlowCNNGeneral") == 0) {
        return ("Digitization of ROIs");
    }

	
    #ifdef ENABLE_WEBHOOK
        if (_input.compare("ClassFlowWebhook") == 0) {
            return ("Sending Webhook");
        }
    #endif //ENABLE_WEBHOOK
	
    if (_input.compare("ClassFlowPostProcessing") == 0) {
        return ("Post-Processing");
    }

    return "Unkown Status";
}

std::vector<HTMLInfo*> ClassFlowControl::GetAllDigit() 
{
    if (flowdigit) {
        ESP_LOGD(TAG, "ClassFlowControl::GetAllDigit - flowdigit != NULL");
        return flowdigit->GetHTMLInfo();
    }

    std::vector<HTMLInfo*> empty;
    return empty;
}

std::vector<HTMLInfo*> ClassFlowControl::GetAllAnalog()
{
    if (flowanalog) {
        return flowanalog->GetHTMLInfo();
    }

    std::vector<HTMLInfo*> empty;
    return empty;
}

t_CNNType ClassFlowControl::GetTypeDigit()
{
    if (flowdigit) {
        return flowdigit->getCNNType();
    }

    return t_CNNType::None;
}

t_CNNType ClassFlowControl::GetTypeAnalog()
{
    if (flowanalog) {
        return flowanalog->getCNNType();
    }

    return t_CNNType::None;
}

#ifdef ALGROI_LOAD_FROM_MEM_AS_JPG
void ClassFlowControl::DigitDrawROI(CImageBasis *_zw)
{
    if (flowdigit) {
        flowdigit->DrawROI(_zw);
    }
}

void ClassFlowControl::AnalogDrawROI(CImageBasis *_zw)
{
    if (flowanalog) {
        flowanalog->DrawROI(_zw);
    }
}
#endif


void ClassFlowControl::SetInitialParameter(void)
{
    AutoStart = true;
    SetupModeActive = false;
    AutoInterval = 10; // Minutes
    AutoWait = 5; // Minutes
    flowdigit = NULL;
    flowanalog = NULL;
    flowpostprocessing = NULL;
    disabled = false;
    aktRunNr = 0;
    aktstatus = "Flow task not yet created";
    aktstatusWithTime = aktstatus;
}

bool ClassFlowControl::getIsAutoStart(void)
{
    //return AutoStart;
    return true; // Flow must always be enabled, else the manual trigger (REST) will not work!
}

float ClassFlowControl::getAutoWait(void)
{
    return AutoWait;
}


void ClassFlowControl::setAutoStartInterval(long &_interval)
{
    _interval = AutoInterval * 60 * 1000; // AutoInterval: minutes -> ms
}

ClassFlow* ClassFlowControl::CreateClassFlow(std::string _type)
{
    ClassFlow* cfc = NULL;
    _type = trim(_type);

    if (toUpper(_type).compare("[TAKEIMAGE]") == 0) {
        cfc = new ClassFlowTakeImage(&FlowControl);
        flowtakeimage = (ClassFlowTakeImage*) cfc;
    }
	
    if (toUpper(_type).compare("[ALIGNMENT]") == 0) {
        cfc = new ClassFlowAlignment(&FlowControl);
        flowalignment = (ClassFlowAlignment*) cfc;
    }
	
    if (toUpper(_type).compare("[ANALOG]") == 0) {
        cfc = new ClassFlowCNNGeneral(flowalignment);
        flowanalog = (ClassFlowCNNGeneral*) cfc;
    }
	
    if (toUpper(_type).compare(0, 7, "[DIGITS") == 0) {
        cfc = new ClassFlowCNNGeneral(flowalignment);
        flowdigit = (ClassFlowCNNGeneral*) cfc;
    }
	
    #ifdef ENABLE_WEBHOOK
        if (toUpper(_type).compare("[WEBHOOK]") == 0) {
            cfc = new ClassFlowWebhook(&FlowControl);
        }
    #endif //ENABLE_WEBHOOK

    if (toUpper(_type).compare("[POSTPROCESSING]") == 0) {
        cfc = new ClassFlowPostProcessing(&FlowControl, flowanalog, flowdigit); 
        flowpostprocessing = (ClassFlowPostProcessing*) cfc;
    }

    if (cfc) {                           
        // Attached only if it is not [AutoTimer], because this is for FlowControl
        FlowControl.push_back(cfc);
    }

    if (toUpper(_type).compare("[AUTOTIMER]") == 0) {
        cfc = this;
    }

    if (toUpper(_type).compare("[DATALOGGING]") == 0) {
        cfc = this;
    }

    if (toUpper(_type).compare("[DEBUG]") == 0) {
        cfc = this;
    }

    if (toUpper(_type).compare("[SYSTEM]") == 0) {
        cfc = this;
    }

    return cfc;
}

void ClassFlowControl::InitFlow(std::string config)
{
    aktstatus = "Initialization";
    aktstatusWithTime = aktstatus;
    
    string line;
    flowpostprocessing = NULL;

    ClassFlow* cfc;
    FILE* pFile;
    config = FormatFileName(config);
    pFile = fopen(config.c_str(), "r");

    line = "";

    char zw[1024];
	
    if (pFile != NULL) {
        fgets(zw, 1024, pFile);
        ESP_LOGD(TAG, "%s", zw);
        line = std::string(zw);
    }

    while ((line.size() > 0) && !(feof(pFile))) {
        cfc = CreateClassFlow(line);
        // printf("Name: %s\n", cfc->name().c_str());
	    
        if (cfc) {
            ESP_LOGE(TAG, "Start ReadParameter (%s)", line.c_str());
            cfc->ReadParameter(pFile, line);
        }
        else {
            line = "";
		
            if (fgets(zw, 1024, pFile) && !feof(pFile)) {
                ESP_LOGD(TAG, "Read: %s", zw);
                line = std::string(zw);
            }
        }
    }

    fclose(pFile);
}

std::string* ClassFlowControl::getActStatusWithTime()
{
    return &aktstatusWithTime;
}

std::string* ClassFlowControl::getActStatus()
{
    return &aktstatus;
}

void ClassFlowControl::setActStatus(std::string _aktstatus)
{
    aktstatus = _aktstatus;
    aktstatusWithTime = aktstatus;
}

void ClassFlowControl::doFlowTakeImageOnly(string time)
{
    std::string zw_time;

    for (int i = 0; i < FlowControl.size(); ++i) {
        if (FlowControl[i]->name() == "ClassFlowTakeImage") {
            zw_time = getCurrentTimeString("%H:%M:%S");
            aktstatus = TranslateAktstatus(FlowControl[i]->name());
            aktstatusWithTime = aktstatus + " (" + zw_time + ")";
            FlowControl[i]->doFlow(time);
        }
    }
}

bool ClassFlowControl::doFlow(string time)
{
    bool result = true;
    std::string zw_time;
    int repeat = 0;
    int qos = 1;

    #ifdef DEBUG_DETAIL_ON 
        LogFile.WriteHeapInfo("ClassFlowControl::doFlow - Start");
    #endif

    /* Check if we have a valid date/time and if not restart the NTP client */
   /* if (! getTimeIsSet()) {
        LogFile.WriteToFile(ESP_LOG_WARN, TAG, "Time not set, restarting NTP Client!");
        restartNtpClient();
    }*/

    //checkNtpStatus(0);

    for (int i = 0; i < FlowControl.size(); ++i) {
        zw_time = getCurrentTimeString("%H:%M:%S");
        aktstatus = TranslateAktstatus(FlowControl[i]->name());
        aktstatusWithTime = aktstatus + " (" + zw_time + ")";
        LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "Status: " + aktstatusWithTime);


        #ifdef DEBUG_DETAIL_ON
            string zw = "FlowControl.doFlow - " + FlowControl[i]->name();
            LogFile.WriteHeapInfo(zw);
        #endif

        if (!FlowControl[i]->doFlow(time)) {
            repeat++;
            LogFile.WriteToFile(ESP_LOG_WARN, TAG, "Fehler im vorheriger Schritt - wird zum " + to_string(repeat) + ". Mal wiederholt");
            if (i) { i -= 1; }   // vPrevious step must be repeated (probably take pictures)
            result = false;
            if (repeat > 5) {
                LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "Wiederholung 5x nicht erfolgreich --> reboot");
                doReboot();
                //Step was repeated 5x --> reboot
            }
        }
        else {
            result = true;
        }
        
        #ifdef DEBUG_DETAIL_ON  
            LogFile.WriteHeapInfo("ClassFlowControl::doFlow");
        #endif
    }

    zw_time = getCurrentTimeString("%H:%M:%S");
    aktstatus = "Flow finished";
    aktstatusWithTime = aktstatus + " (" + zw_time + ")";
    //LogFile.WriteToFile(ESP_LOG_INFO, TAG, aktstatusWithTime);


    return result;
}


string ClassFlowControl::getReadoutAll(int _type)
{
    std::string out = "";
	
    if (flowpostprocessing) {
        std::vector<NumberPost*> *numbers = flowpostprocessing->GetNumbers();

        for (int i = 0; i < (*numbers).size(); ++i) {
            out = out + (*numbers)[i]->name + "\t";
		
            switch (_type) {
                case READOUT_TYPE_VALUE:
                    out = out + (*numbers)[i]->ReturnValue;
                    break;
                case READOUT_TYPE_PREVALUE:
                    if (flowpostprocessing->PreValueUse) {
                        if ((*numbers)[i]->PreValueOkay) {
                            out = out + (*numbers)[i]->ReturnPreValue;
                        }
                        else {
                            out = out + "PreValue too old"; 
                        }
                    }
                    else {
                        out = out + "PreValue deactivated";
                    }
                    break;
                case READOUT_TYPE_RAWVALUE:
                    out = out + (*numbers)[i]->ReturnRawValue;
                    break;
                case READOUT_TYPE_ERROR:
                    out = out + (*numbers)[i]->ErrorMessageText;
                    break;
            }
		
            if (i < (*numbers).size()-1) {
                out = out + "\r\n";
            }
        }
    // ESP_LOGD(TAG, "OUT: %s", out.c_str());
    }

    return out;
}	

string ClassFlowControl::getReadout(bool _rawvalue = false, bool _noerror = false, int _number = 0)
{
    if (flowpostprocessing) {
        return flowpostprocessing->getReadoutParam(_rawvalue, _noerror, _number);
    }

    return std::string("");
}

string ClassFlowControl::GetPrevalue(std::string _number)	
{
    if (flowpostprocessing) {
        return flowpostprocessing->GetPreValue(_number);   
    }

    return std::string("");    
}

bool ClassFlowControl::UpdatePrevalue(std::string _newvalue, std::string _numbers, bool _extern)
{
    double newvalueAsDouble;
    char* p;

    _newvalue = trim(_newvalue);
    // ESP_LOGD(TAG, "Input UpdatePreValue: %s", _newvalue.c_str());

    if (_newvalue.substr(0,8).compare("0.000000") == 0 || _newvalue.compare("0.0") == 0 || _newvalue.compare("0") == 0) {
        newvalueAsDouble = 0;   // preset to value = 0
    }
    else {
        newvalueAsDouble = strtod(_newvalue.c_str(), &p);
        if (newvalueAsDouble == 0) {
            LogFile.WriteToFile(ESP_LOG_WARN, TAG, "UpdatePrevalue: No valid value for processing: " + _newvalue);
            return false;
        }
    }
    
    if (flowpostprocessing) {
        if (flowpostprocessing->SetPreValue(newvalueAsDouble, _numbers, _extern)) {
            return true;
        }
        else {
            return false;
        }
    }
    else {
        LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "UpdatePrevalue: ERROR - Class Post-Processing not initialized");
        return false;
    }
}

bool ClassFlowControl::ReadParameter(FILE* pfile, string& aktparamgraph)
{
    std::vector<string> splitted;
    aktparamgraph = trim(aktparamgraph);

    if (aktparamgraph.size() == 0) {
        if (!this->GetNextParagraph(pfile, aktparamgraph)) {
            return false;
        }
    }

    if ((toUpper(aktparamgraph).compare("[AUTOTIMER]") != 0) && (toUpper(aktparamgraph).compare("[DEBUG]") != 0) &&
        (toUpper(aktparamgraph).compare("[SYSTEM]") != 0 && (toUpper(aktparamgraph).compare("[DATALOGGING]") != 0))) {     
        // Paragraph passt nicht zu Debug oder DataLogging
        return false;
    }

    while (this->getNextLine(pfile, &aktparamgraph) && !this->isNewParagraph(aktparamgraph)) {
        splitted = ZerlegeZeile(aktparamgraph, " =");

        if ((toUpper(splitted[0]) == "INTERVAL") && (splitted.size() > 1)) {
            if (isStringNumeric(splitted[1])) {
                AutoInterval = std::stof(splitted[1]);
            }
        }

        if ((toUpper(splitted[0]) == "WAIT") && (splitted.size() > 1)) {
            if (isStringNumeric(splitted[1])) {
                AutoWait = std::stof(splitted[1]);
            }
        }

        if ((toUpper(splitted[0]) == "DATALOGACTIVE") && (splitted.size() > 1)) {
            LogFile.SetDataLogToSD(alphanumericToBoolean(splitted[1]));
        }

        if ((toUpper(splitted[0]) == "DATAFILESRETENTION") && (splitted.size() > 1)) {
            if (isStringNumeric(splitted[1])) {
                LogFile.SetDataLogRetention(std::stoi(splitted[1]));
            }
        }

        if ((toUpper(splitted[0]) == "LOGLEVEL") && (splitted.size() > 1)) {
            /* matches esp_log_level_t */
            if ((toUpper(splitted[1]) == "TRUE") || (toUpper(splitted[1]) == "2")) {
                LogFile.setLogLevel(ESP_LOG_WARN);
            }
            else if ((toUpper(splitted[1]) == "FALSE") || (toUpper(splitted[1]) == "0") || (toUpper(splitted[1]) == "1")) {
                LogFile.setLogLevel(ESP_LOG_ERROR);
            }
            else if (toUpper(splitted[1]) == "3") {
                LogFile.setLogLevel(ESP_LOG_INFO);
            }
            else if (toUpper(splitted[1]) == "4") {
                LogFile.setLogLevel(ESP_LOG_DEBUG);
            }

            /* If system reboot was not triggered by user and reboot was caused by execption -> keep log level to DEBUG */
            if (!getIsPlannedReboot() && (esp_reset_reason() == ESP_RST_PANIC)) {
                LogFile.setLogLevel(ESP_LOG_DEBUG);
            }
        }
	    
        if ((toUpper(splitted[0]) == "LOGFILESRETENTION") && (splitted.size() > 1)) {
            if (isStringNumeric(splitted[1])) {
                LogFile.SetLogFileRetention(std::stoi(splitted[1]));
            }
        }

        /* TimeServer and TimeZone got already read from the config, see setupTime () */
        
        #if (defined WLAN_USE_ROAMING_BY_SCANNING || (defined WLAN_USE_MESH_ROAMING && defined WLAN_USE_MESH_ROAMING_ACTIVATE_CLIENT_TRIGGERED_QUERIES))
        if ((toUpper(splitted[0]) == "RSSITHRESHOLD") && (splitted.size() > 1)) {
            int RSSIThresholdTMP = atoi(splitted[1].c_str());
            RSSIThresholdTMP = min(0, max(-100, RSSIThresholdTMP)); // Verify input limits (-100 - 0)
            
            if (ChangeRSSIThreshold(WLAN_CONFIG_FILE, RSSIThresholdTMP)) {
                // reboot necessary so that the new wlan.ini is also used !!!
                fclose(pfile);
                LogFile.WriteToFile(ESP_LOG_WARN, TAG, "Rebooting to activate new RSSITHRESHOLD ...");
                doReboot();
            }
        }
        #endif

        if ((toUpper(splitted[0]) == "HOSTNAME") && (splitted.size() > 1)) {
            if (ChangeHostName(WLAN_CONFIG_FILE, splitted[1])) {
                // reboot necessary so that the new wlan.ini is also used !!!
                fclose(pfile);
                LogFile.WriteToFile(ESP_LOG_WARN, TAG, "Rebooting to activate new HOSTNAME...");             
                doReboot();
            }
        }

        if ((toUpper(splitted[0]) == "SETUPMODE") && (splitted.size() > 1)) {
            SetupModeActive = alphanumericToBoolean(splitted[1]);        
        }
    }
    return true;
}

int ClassFlowControl::CleanTempFolder() {
    const char* folderPath = "/sdcard/img_tmp";
    
    ESP_LOGD(TAG, "Clean up temporary folder to avoid damage of sdcard sectors: %s", folderPath);
    DIR *dir = opendir(folderPath);
	
    if (!dir) {
        ESP_LOGE(TAG, "Failed to stat dir: %s", folderPath);
        return -1;
    }

    struct dirent *entry;
    int deleted = 0;
	
    while ((entry = readdir(dir)) != NULL) {
        std::string path = string(folderPath) + "/" + entry->d_name;
        if (entry->d_type == DT_REG) {
            if (unlink(path.c_str()) == 0) {
                deleted ++;
            } 
            else {
                ESP_LOGE(TAG, "can't delete file: %s", path.c_str());
            }
        } 
        else if (entry->d_type == DT_DIR) {
            deleted += removeFolder(path.c_str(), TAG);
        }
    }
	
    closedir(dir);
    ESP_LOGD(TAG, "%d files deleted", deleted);
    
    return 0;
}

esp_err_t ClassFlowControl::SendRawJPG(httpd_req_t *req)
{
    return flowtakeimage != NULL ? flowtakeimage->SendRawJPG(req) : ESP_FAIL;
}

esp_err_t ClassFlowControl::GetJPGStream(std::string _fn, httpd_req_t *req)
{
    ESP_LOGD(TAG, "ClassFlowControl::GetJPGStream %s", _fn.c_str());

    #ifdef DEBUG_DETAIL_ON 
        LogFile.WriteHeapInfo("ClassFlowControl::GetJPGStream - Start");
    #endif

    CImageBasis *_send = NULL;
    esp_err_t result = ESP_FAIL;
    bool _sendDelete = false;

    if (_fn == "alg.jpg") {
        if (flowalignment && flowalignment->ImageBasis->ImageOkay()) {
            _send = flowalignment->ImageBasis;
        }
        else {
            LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "ClassFlowControl::GetJPGStream: alg.jpg cannot be served");
            return ESP_FAIL;
        }
    }
    else if (_fn == "alg_roi.jpg") {
        #ifdef ALGROI_LOAD_FROM_MEM_AS_JPG      // no CImageBasis needed to create alg_roi.jpg (ca. 790kB less RAM)
            if (aktstatus.find("Initialization (delayed)") != -1) {
                std::string filename = "/sdcard/html/Flowstate_initialization_delayed.jpg";
                result = send_file(req, filename);
                /*    
                FILE* file = fopen("/sdcard/html/Flowstate_initialization_delayed.jpg", "rb"); 

                if (!file) {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "File /sdcard/html/Flowstate_initialization_delayed.jpg not found");
                    return ESP_FAIL;
                }

                fseek(file, 0, SEEK_END);
                long fileSize = ftell(file); // how long is the file ?
                fseek(file, 0, SEEK_SET); // reset

                unsigned char* fileBuffer = (unsigned char*) malloc(fileSize);

                if (!fileBuffer) {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "ClassFlowControl::GetJPGStream: Not enough memory to create fileBuffer: " + std::to_string(fileSize));
                    fclose(file);  
                    return ESP_FAIL;
                }

                fread(fileBuffer, fileSize, 1, file);
                fclose(file);

                httpd_resp_set_type(req, "image/jpeg");
                result = httpd_resp_send(req, (const char *)fileBuffer, fileSize); 
                free(fileBuffer);
                */
            }
            else if (aktstatus.find("Initialization") != -1) {
                std::string filename = "/sdcard/html/Flowstate_initialization.jpg";
                result = send_file(req, filename);
                /*
                FILE* file = fopen("/sdcard/html/Flowstate_initialization.jpg", "rb"); 

                if (!file) {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "File /sdcard/html/Flowstate_initialization.jpg not found");
                    return ESP_FAIL;
                }

                fseek(file, 0, SEEK_END);
                long fileSize = ftell(file); // how long is the file ?
                fseek(file, 0, SEEK_SET); // reset

                unsigned char* fileBuffer = (unsigned char*) malloc(fileSize);

                if (!fileBuffer) {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "ClassFlowControl::GetJPGStream: Not enough memory to create fileBuffer: " + std::to_string(fileSize));
                    fclose(file);  
                    return ESP_FAIL;
                }

                fread(fileBuffer, fileSize, 1, file);
                fclose(file);

                httpd_resp_set_type(req, "image/jpeg");
                result = httpd_resp_send(req, (const char *)fileBuffer, fileSize); 
                free(fileBuffer);
                */
            }
            else if (aktstatus.find("Take Image") != -1) {
                if (flowalignment && flowalignment->AlgROI) {
                    std::string filename = "/sdcard/html/Flowstate_take_image.jpg";
                    result = send_file(req, filename);
                    /*
                    FILE* file = fopen("/sdcard/html/Flowstate_take_image.jpg", "rb");    

                    if (!file) {
                        LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "File /sdcard/html/Flowstate_take_image.jpg not found");
                        return ESP_FAIL;
                    }

                    fseek(file, 0, SEEK_END);
                    flowalignment->AlgROI->size = ftell(file); // how long is the file ?
                    fseek(file, 0, SEEK_SET); // reset
                    
                    if (flowalignment->AlgROI->size > MAX_JPG_SIZE) {
                        LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "File /sdcard/html/Flowstate_take_image.jpg (" + std::to_string(flowalignment->AlgROI->size) +
                                                                ") > allocated buffer (" + std::to_string(MAX_JPG_SIZE) + ")");
                        fclose(file);
                        return ESP_FAIL;
                    }

                    fread(flowalignment->AlgROI->data, flowalignment->AlgROI->size, 1, file);
                    fclose(file);

                    httpd_resp_set_type(req, "image/jpeg");
                    result = httpd_resp_send(req, (const char *)flowalignment->AlgROI->data, flowalignment->AlgROI->size);
                    */
                }
                else {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "ClassFlowControl::GetJPGStream: alg_roi.jpg cannot be served -> alg.jpg is going to be served!");
                    if (flowalignment && flowalignment->ImageBasis->ImageOkay()) {
                        _send = flowalignment->ImageBasis;
                    }
                    else {
                        httpd_resp_send(req, NULL, 0);
                        return ESP_OK;
                    }
                }
            }
            else {
                if (flowalignment && flowalignment->AlgROI) {
                    httpd_resp_set_type(req, "image/jpeg");
                    result = httpd_resp_send(req, (const char *)flowalignment->AlgROI->data, flowalignment->AlgROI->size);
                }
                else {
                    LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "ClassFlowControl::GetJPGStream: alg_roi.jpg cannot be served -> alg.jpg is going to be served!");
                    if (flowalignment && flowalignment->ImageBasis->ImageOkay()) {
                        _send = flowalignment->ImageBasis;
                    }
                    else {
                        httpd_resp_send(req, NULL, 0);
                        return ESP_OK;
                    }
                }
            }
        #else
            if (!flowalignment) {
                ESP_LOGD(TAG, "ClassFloDControll::GetJPGStream: FlowAlignment is not (yet) initialized. Interrupt serving!");
                httpd_resp_send(req, NULL, 0);
                return ESP_FAIL;
            }

            _send = new CImageBasis("alg_roi", flowalignment->ImageBasis);
			
            if (_send->ImageOkay()) {
                if (flowalignment) { flowalignment->DrawRef(_send); }
                if (flowdigit) { flowdigit->DrawROI(_send); }
                if (flowanalog) { flowanalog->DrawROI(_send); }
                _sendDelete = true; // delete temporary _send element after sending
            }
            else {
                LogFile.WriteToFile(ESP_LOG_WARN, TAG, "ClassFlowControl::GetJPGStream: Not enough memory to create alg_roi.jpg -> alg.jpg is going to be served!");
                
                if (flowalignment && flowalignment->ImageBasis->ImageOkay()) {
                    _send = flowalignment->ImageBasis;
                }
                else {
                    httpd_resp_send(req, NULL, 0);
                    return ESP_OK;
                }
            }
        #endif
    }
    else {
        std::vector<HTMLInfo*> htmlinfo;
    
        htmlinfo = GetAllDigit();
        ESP_LOGD(TAG, "After getClassFlowControl::GetAllDigit");

        for (int i = 0; i < htmlinfo.size(); ++i) {
            if (_fn == htmlinfo[i]->filename) {
                if (htmlinfo[i]->image) {
                    _send = htmlinfo[i]->image;
                }
            }

            if (_fn == htmlinfo[i]->filename_org) {
                if (htmlinfo[i]->image_org) {
                    _send = htmlinfo[i]->image_org;
                }
            }
            delete htmlinfo[i];
        }
        htmlinfo.clear();

        if (!_send) {
            htmlinfo = GetAllAnalog();
            ESP_LOGD(TAG, "After getClassFlowControl::GetAllAnalog");
	        
            for (int i = 0; i < htmlinfo.size(); ++i) {
                if (_fn == htmlinfo[i]->filename) {
                    if (htmlinfo[i]->image) {
                        _send = htmlinfo[i]->image;
                    }
                }

                if (_fn == htmlinfo[i]->filename_org) {
                    if (htmlinfo[i]->image_org) {
                        _send = htmlinfo[i]->image_org;
                    }
                }
                delete htmlinfo[i];
            }
            htmlinfo.clear();
        }
    }

    #ifdef DEBUG_DETAIL_ON 
        LogFile.WriteHeapInfo("ClassFlowControl::GetJPGStream - before send");
    #endif

    if (_send) {
        ESP_LOGD(TAG, "Sending file: %s ...", _fn.c_str());
        set_content_type_from_file(req, _fn.c_str());
        result = _send->SendJPGtoHTTP(req);
	
        /* Respond with an empty chunk to signal HTTP response completion */
        httpd_resp_send_chunk(req, NULL, 0);
        ESP_LOGD(TAG, "File sending complete");

        if (_sendDelete) {
            delete _send;
        }
            
        _send = NULL;  
    }

    #ifdef DEBUG_DETAIL_ON 
        LogFile.WriteHeapInfo("ClassFlowControl::GetJPGStream - done");
    #endif

    return result;
}

string ClassFlowControl::getNumbersName()
{
    return flowpostprocessing->getNumbersName();
}

string ClassFlowControl::getJSON()
{
    return flowpostprocessing->GetJSON();
}

/** 
 * @returns a vector of all current sequences
 **/
const std::vector<NumberPost*> &ClassFlowControl::getNumbers()
{
    return *flowpostprocessing->GetNumbers();
}
