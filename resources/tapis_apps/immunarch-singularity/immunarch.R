# Code to run Immunarch on a sinlge repertoire on data from the
# iReceptor Gateway.
library(immunarch)
library(xtable)

# Test for arguments
args = commandArgs(trailingOnly=TRUE)
if (length(args)!=2) {
  stop("An input and output directory must be supplied.n", call.=FALSE)
}

# Set our error handling variable
ir_error_count <- 0

# Get the input and output directories
input_dir <- args[1]
output_dir <- args[2]

# Load data. Need a separate tsv per repertoire. Metadata file is optional,
# but allows for grouped comparisons.
data <- repLoad(input_dir)

# Distribution of clonotype abundances, i.e., how frequent receptors with different abundances are
result = tryCatch(
{
    exp_count <- repExplore(data$data, .method = "count")
    exp_count_vis <- vis(exp_count)
    ggsave(paste(output_dir, "count.png", sep="/"), plot = exp_count_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Distribution of CDR3 sequence lengths
result = tryCatch(
{
    exp_len <- repExplore(data$data, .method = "len", .col="aa")
    exp_len_vis <- vis(exp_len)
    ggsave(paste(output_dir, "len.png", sep="/"), plot = exp_len_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Top 10 clones
result = tryCatch(
{
    print('top_10_clones.tsv')
    top_10 <- top(data$data[[1]], .n = 10)
    write.table(top_10, file = paste(output_dir, "top_10_clones.tsv", sep="/"), sep = "\t",row.names=FALSE)
    print(xtable(top_10[0:10,c(1,2,4,5,7)]), type = "html", file=paste(output_dir, "top_10_clones.html", sep="/"))
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Relative abundance for the groups of rare clonotypes with low counts. Use ".bound"
# to define the threshold of clonotype groups.
result = tryCatch(
{
    print("clonal_rare.png")
    clon_rare <- repClonality(data$data, .method = "rare")
    clon_rare_vis <- vis(clon_rare)
    ggsave(paste(output_dir, "clonal_rare.png", sep="/"), plot = clon_rare_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Relative abundance (also known as clonal space homeostasis), which is defined as the
# proportion of repertoire occupied by clonal groups with specific abundances.
result = tryCatch(
{
    print("clonal_homeo.png")
    clon_hom <- repClonality(data$data, .method = "homeo")
    clon_hom_vis <- vis(clon_hom)
    ggsave(paste(output_dir, "clonal_homeo.png", sep="/"), plot = clon_hom_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Gene segment usage, normalized
result = tryCatch(
{
    print("gene_usage_normalized.png")
    gu_norm <- geneUsage(data$data, .norm = T)
    gu_norm_vis <- vis(gu_norm)
    ggsave(paste(output_dir, "gene_usage_normalized.png", sep="/"), plot = gu_norm_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# Gene family usage, normalized
result = tryCatch(
{
    print("gene_family_usage_normalized.png")
    gu_fam_norm <- geneUsage(data$data, .norm = T, .type = "family")
    gu_fam_norm_vis <- vis(gu_fam_norm)
    ggsave(paste(output_dir, "gene_family_usage_normalized.png", sep="/"), plot = gu_fam_norm_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

