#ifdef ENABLE_WEBHOOK
#include <sstream>
#include "ClassFlowWebhook.h"
#include "Helper.h"
#include "connect_wlan.h"

#include "time_sntp.h"
#include "interface_webhook.h"

#include "ClassFlowPostProcessing.h"
#include "ClassFlowAlignment.h"
#include "esp_log.h"
#include "../../include/defines.h"

#include "ClassLogFile.h"

#include <time.h>

static const char* TAG = "WEBHOOK";

void ClassFlowWebhook::SetInitialParameter(void)
{
    uri = "";
    flowpostprocessing = NULL;
    flowAlignment = NULL;
    previousElement = NULL;
    ListFlowControl = NULL; 
    disabled = false;
    WebhookEnable = false;
    WebhookUploadImg = 0;
}       

ClassFlowWebhook::ClassFlowWebhook()
{
    SetInitialParameter();
}

ClassFlowWebhook::ClassFlowWebhook(std::vector<ClassFlow*>* lfc)
{
    SetInitialParameter();

    ListFlowControl = lfc;
    for (int i = 0; i < ListFlowControl->size(); ++i)
    {
        if (((*ListFlowControl)[i])->name().compare("ClassFlowPostProcessing") == 0)
        {
            flowpostprocessing = (ClassFlowPostProcessing*) (*ListFlowControl)[i];
        }
        if (((*ListFlowControl)[i])->name().compare("ClassFlowAlignment") == 0)
        {
            flowAlignment = (ClassFlowAlignment*) (*ListFlowControl)[i];
        }

    }
}

ClassFlowWebhook::ClassFlowWebhook(std::vector<ClassFlow*>* lfc, ClassFlow *_prev)
{
    SetInitialParameter();

    previousElement = _prev;
    ListFlowControl = lfc;

    for (int i = 0; i < ListFlowControl->size(); ++i)
    {
        if (((*ListFlowControl)[i])->name().compare("ClassFlowPostProcessing") == 0)
        {
            flowpostprocessing = (ClassFlowPostProcessing*) (*ListFlowControl)[i];
        }
        if (((*ListFlowControl)[i])->name().compare("ClassFlowAlignment") == 0)
        {
            flowAlignment = (ClassFlowAlignment*) (*ListFlowControl)[i];
        }
    }
}


bool ClassFlowWebhook::ReadParameter(FILE* pfile, string& aktparamgraph)
{
    std::vector<string> splitted;

    aktparamgraph = trim(aktparamgraph);
    printf("akt param: %s\n", aktparamgraph.c_str());

    if (aktparamgraph.size() == 0)
        if (!this->GetNextParagraph(pfile, aktparamgraph))
            return false;

    if (toUpper(aktparamgraph).compare("[WEBHOOK]") != 0) 
        return false;

    

    while (this->getNextLine(pfile, &aktparamgraph) && !this->isNewParagraph(aktparamgraph))
    {
        ESP_LOGD(TAG, "while loop reading line: %s", aktparamgraph.c_str());
        splitted = ZerlegeZeile(aktparamgraph);
        std::string _param = GetParameterName(splitted[0]);
            
        if ((toUpper(_param) == "URI") && (splitted.size() > 1))
        {
            this->uri = splitted[1];
        }
        if (((toUpper(_param) == "APIKEY")) && (splitted.size() > 1))
        {
            this->apikey = splitted[1];
        }
        if (((toUpper(_param) == "UPLOADIMG")) && (splitted.size() > 1))
        {
            if (toUpper(splitted[1]) == "1")
            {
                this->WebhookUploadImg = 1;
            } else if (toUpper(splitted[1]) == "2")
            {
                this->WebhookUploadImg = 2;
            }
        }
    }
    
    WebhookInit(uri,apikey);
    WebhookEnable = true;
    LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "Webhook Enabled for Uri " + uri);

    printf("uri:         %s\n", uri.c_str());   
    return true;
}



bool ClassFlowWebhook::doFlow(string zwtime)
{
    if (!WebhookEnable)
        return true;

    if (flowpostprocessing)
    {
        bool numbersWithError = WebhookPublish(flowpostprocessing->GetNumbers());

        #ifdef ALGROI_LOAD_FROM_MEM_AS_JPG
            if ((WebhookUploadImg == 1 || (WebhookUploadImg != 0 && numbersWithError)) && flowAlignment && flowAlignment->AlgROI) {
                WebhookUploadPic(flowAlignment->AlgROI);
            }
        #endif
    }
       
    return true;
}
#endif //ENABLE_WEBHOOK